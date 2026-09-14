<?php

namespace App\Modules\Notifications\Notifications;

use App\Modules\Companies\Models\Company;
use Illuminate\Notifications\Notification;

/**
 * Sent to a company's Admin-role users when its trial is about to
 * expire - the first real trigger for this module, chosen because
 * companies.trial_ends_at has existed since Module 2 and needs no
 * Phase C domain data (leases, invoices) to be meaningful. Dispatched
 * by App\Modules\Notifications\Console\Commands\NotifyTrialsEndingSoon.
 *
 * In-app + email only, deliberately not SMS - a trial reminder is
 * informational, not urgent enough to justify spending real SMS
 * credit on every admin of every trialing company. A future
 * notification with a stronger case (e.g. a failed payment) can opt
 * into 'company-sms' via its own via().
 */
class TrialEndingSoon extends Notification
{
    public function __construct(protected Company $company)
    {
    }

    /**
     * @return array<int, string>
     */
    public function via(mixed $notifiable): array
    {
        return ['database', 'company-mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'company_id' => $this->company->id,
            'trial_ends_at' => $this->company->trial_ends_at?->toDateString(),
            'title' => 'Trial ending soon',
            'message' => "{$this->company->name}'s trial ends on {$this->company->trial_ends_at?->format('M j, Y')}.",
            'url' => route('companies.show', $this->company),
        ];
    }

    /**
     * @return array{subject: string, body: string}
     */
    public function toCompanyMail(mixed $notifiable): array
    {
        $date = $this->company->trial_ends_at?->format('M j, Y');

        return [
            'subject' => "Your trial ends on {$date}",
            'body' => "Hi {$notifiable->name},\n\n"
                ."{$this->company->name}'s trial ends on {$date}. "
                ."Upgrade before then to keep everything running without interruption.\n\n"
                .route('companies.show', $this->company),
        ];
    }
}
