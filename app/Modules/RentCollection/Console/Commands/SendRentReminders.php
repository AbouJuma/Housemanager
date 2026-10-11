<?php

namespace App\Modules\RentCollection\Console\Commands;

use App\Modules\Companies\Models\Company;
use App\Modules\Invoices\Models\Invoice;
use App\Modules\PaymentReminders\Models\PaymentReminderRule;
use App\Modules\PaymentReminders\Services\ReminderRuleService;
use App\Modules\RentCollection\Notifications\InvoiceDueSoon;
use App\Modules\RentCollection\Notifications\InvoiceOverdue;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Console\Command;

/**
 * Scheduled daily (see RentCollectionServiceProvider::boot()). Runs
 * after MarkOverdueInvoices in the schedule so a freshly-overdue
 * invoice gets its InvoiceOverdue reminder the same day it flips,
 * rather than a day late.
 *
 * Cadence is per-company configurable via
 * App\Modules\PaymentReminders\Models\PaymentReminderRule (context
 * 'rent') instead of a hardcoded 3-day/7-day literal - see
 * ReminderRuleService::defaultRuleDefinitions() for what a new company
 * starts with. A reminder never fires for a lease that's no longer
 * active (tenant moved out / lease terminated) or an invoice that's
 * already fully paid - this isn't a configurable rule, it's always the
 * correct behavior.
 */
class SendRentReminders extends Command
{
    protected $signature = 'rent:send-reminders';

    protected $description = 'Send configurable due-soon and overdue rent reminders to tenants';

    public function __construct(protected ReminderRuleService $ruleService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $sent = 0;

        Company::query()->each(function (Company $company) use (&$sent): void {
            foreach ($this->ruleService->rulesFor($company, PaymentReminderRule::CONTEXT_RENT) as $rule) {
                $sent += $rule->isBeforeOrOnDueDate()
                    ? $this->sendForDueSoonRule($company, $rule)
                    : $this->sendForOverdueRule($company, $rule);
            }
        });

        $this->info("Sent {$sent} rent reminder(s).");

        return self::SUCCESS;
    }

    /**
     * Fires once per invoice per rule, on the exact day offset_days
     * before due_date - the same "exact day, not a rolling window"
     * discipline NotifyTrialsEndingSoon uses, so re-running the command
     * daily doesn't re-notify. due_date is immutable once an invoice is
     * sent (Module 17's editable-only-while-draft rule), so a
     * rule_id+invoice_id dedupe check is sufficient.
     */
    protected function sendForDueSoonRule(Company $company, PaymentReminderRule $rule): int
    {
        $targetDate = now()->addDays($rule->offset_days)->toDateString();

        $invoices = Invoice::query()
            ->forCompany($company->id)
            ->whereIn('status', ['sent', 'partially_paid'])
            ->whereDate('due_date', $targetDate)
            ->with('lease.tenant')
            ->get();

        $sent = 0;

        foreach ($invoices as $invoice) {
            $tenant = $invoice->lease?->tenant;

            if (! $tenant || ! $invoice->lease->isActive() || $invoice->balanceDue() <= 0) {
                continue;
            }

            if ($this->alreadyNotified($tenant, InvoiceDueSoon::class, $rule->id, $invoice->id)) {
                continue;
            }

            $tenant->notify(new InvoiceDueSoon($invoice, $rule));
            $sent++;
        }

        return $sent;
    }

    /**
     * Re-notifies every repeat_every_days while an invoice stays
     * overdue (null = fire once and never again), unlike the due-soon
     * reminder's always-one-shot behavior.
     */
    protected function sendForOverdueRule(Company $company, PaymentReminderRule $rule): int
    {
        // abs(offset_days) = how many days overdue before this rule
        // starts applying, so two overdue rules (e.g. -1 and -10) each
        // match a different, meaningful set of invoices instead of
        // both firing off the same "status = overdue" set.
        $sinceDate = now()->subDays(abs($rule->offset_days))->toDateString();

        $invoices = Invoice::query()
            ->forCompany($company->id)
            ->where('status', 'overdue')
            ->whereDate('due_date', '<=', $sinceDate)
            ->with('lease.tenant')
            ->get();

        $sent = 0;

        foreach ($invoices as $invoice) {
            $tenant = $invoice->lease?->tenant;

            if (! $tenant || ! $invoice->lease->isActive() || $invoice->balanceDue() <= 0) {
                continue;
            }

            if ($this->alreadyNotified($tenant, InvoiceOverdue::class, $rule->id, $invoice->id, $rule->repeat_every_days)) {
                continue;
            }

            $tenant->notify(new InvoiceOverdue($invoice, $rule));
            $sent++;
        }

        return $sent;
    }

    protected function alreadyNotified(Tenant $tenant, string $notificationClass, int $ruleId, int $invoiceId, ?int $withinDays = null): bool
    {
        return $tenant->notifications()
            ->where('type', $notificationClass)
            ->where('data->invoice_id', $invoiceId)
            ->where('data->rule_id', $ruleId)
            ->when($withinDays, fn ($query) => $query->where('created_at', '>=', now()->subDays($withinDays)))
            ->exists();
    }
}
