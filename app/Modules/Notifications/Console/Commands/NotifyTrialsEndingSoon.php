<?php

namespace App\Modules\Notifications\Console\Commands;

use App\Modules\Companies\Models\Company;
use App\Modules\Notifications\Notifications\TrialEndingSoon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Scheduled daily (see App\Modules\Notifications\Providers\
 * NotificationsServiceProvider's boot() - registered in routes/console.php).
 * Notifies a company's Admin-role users exactly once, on the day its
 * trial is 3 days from ending, not on a rolling "within N days" window
 * - that would re-notify every day the command runs.
 */
class NotifyTrialsEndingSoon extends Command
{
    protected $signature = 'notifications:trial-ending';

    protected $description = "Notify company admins whose trial ends in 3 days";

    public function handle(): int
    {
        $targetDate = now()->addDays(3)->toDateString();

        $companies = Company::query()
            ->where('status', 'trial')
            ->whereDate('trial_ends_at', $targetDate)
            ->get();

        $notified = 0;

        foreach ($companies as $company) {
            foreach ($this->adminsOf($company) as $admin) {
                if ($this->alreadyNotified($admin, $company)) {
                    continue;
                }

                $admin->notify(new TrialEndingSoon($company));
                $notified++;
            }
        }

        $this->info("Notified {$notified} admin(s) across {$companies->count()} trialing company(ies).");

        return self::SUCCESS;
    }

    /**
     * @return \Illuminate\Support\Collection<int, \App\Models\User>
     */
    protected function adminsOf(Company $company): \Illuminate\Support\Collection
    {
        // Direct table join, not Spatie's Role query builder - Role
        // has no automatic team scope on plain queries (see
        // ARCHITECTURE.md's teams caveat; Module 6's Dashboard uses
        // the same direct-join pattern for the same reason).
        $adminUserIds = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.company_id', $company->id)
            ->where('roles.company_id', $company->id)
            ->where('roles.name', 'Admin')
            ->pluck('model_has_roles.model_id');

        return $company->users()->whereIn('id', $adminUserIds)->get();
    }

    protected function alreadyNotified(\App\Models\User $admin, Company $company): bool
    {
        return $admin->notifications()
            ->where('type', TrialEndingSoon::class)
            ->where('data->company_id', $company->id)
            ->where('data->trial_ends_at', $company->trial_ends_at?->toDateString())
            ->exists();
    }
}
