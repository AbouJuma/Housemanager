<?php

namespace App\Modules\Sales\Console\Commands;

use App\Modules\Companies\Models\Company;
use App\Modules\PaymentReminders\Models\PaymentReminderRule;
use App\Modules\PaymentReminders\Services\ReminderRuleService;
use App\Modules\Sales\Models\Installment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Notifications\PaymentDueSoon;
use App\Modules\Sales\Notifications\PaymentOverdue;
use App\Modules\Sales\Notifications\SaleCompleted;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;

/**
 * Scheduled daily - see SalesServiceProvider::boot(). Cadence is
 * per-company configurable via App\Modules\PaymentReminders\Models\
 * PaymentReminderRule (context 'sale_installment'), the same rule
 * engine App\Modules\RentCollection\Console\Commands\SendRentReminders
 * uses for rent - see ReminderRuleService::defaultRuleDefinitions() for
 * what a new company starts with. A reminder never fires for a
 * completed sale or an already-paid installment - this isn't a
 * configurable rule, it's always the correct behavior.
 */
class SendPaymentReminders extends Command
{
    protected $signature = 'sales:send-payment-reminders';

    protected $description = 'Send configurable due-soon and overdue payment reminders to buyers';

    public function __construct(protected ReminderRuleService $ruleService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $sent = 0;

        Company::query()->each(function (Company $company) use (&$sent): void {
            foreach ($this->ruleService->rulesFor($company, PaymentReminderRule::CONTEXT_SALE_INSTALLMENT) as $rule) {
                $sent += $rule->isBeforeOrOnDueDate()
                    ? $this->sendForDueSoonRule($company, $rule)
                    : $this->sendForOverdueRule($company, $rule);
            }

            $sent += $this->sendCompletedSaleNotices($company);
        });

        $this->info("Sent {$sent} payment reminder(s).");

        return self::SUCCESS;
    }

    /**
     * Fires once per installment per rule, on the exact day offset_days
     * before due_date - matches the exact-date discipline
     * SendRentReminders uses for rent due-soon reminders.
     */
    protected function sendForDueSoonRule(Company $company, PaymentReminderRule $rule): int
    {
        $targetDate = now()->addDays($rule->offset_days)->toDateString();

        $installments = Installment::query()
            ->where('status', 'pending')
            ->whereDate('due_date', $targetDate)
            ->whereHas('sale', fn ($query) => $query->forCompany($company->id))
            ->with('sale.buyer')
            ->get();

        $sent = 0;

        foreach ($installments as $installment) {
            $buyer = $installment->sale->buyer;

            if (! $buyer || ! $this->isReminderEligible($installment)) {
                continue;
            }

            if ($this->alreadyNotified($buyer, PaymentDueSoon::class, $rule->id, $installment->id)) {
                continue;
            }

            $buyer->notify(new PaymentDueSoon($installment, $rule));
            $sent++;
        }

        return $sent;
    }

    /**
     * Re-notifies every repeat_every_days while an installment stays
     * overdue (null = fire once and never again).
     */
    protected function sendForOverdueRule(Company $company, PaymentReminderRule $rule): int
    {
        // abs(offset_days) = how many days overdue before this rule
        // starts applying - see SendRentReminders::sendForOverdueRule()
        // for why this can't just be "status = overdue" alone.
        $sinceDate = now()->subDays(abs($rule->offset_days));

        $installments = Installment::query()
            ->whereIn('status', ['pending', 'overdue'])
            ->where('due_date', '<=', $sinceDate)
            ->whereHas('sale', fn ($query) => $query->forCompany($company->id))
            ->with('sale.buyer')
            ->get();

        $sent = 0;

        foreach ($installments as $installment) {
            $buyer = $installment->sale->buyer;

            if (! $buyer || ! $this->isReminderEligible($installment)) {
                continue;
            }

            $installment->markAsOverdue();

            if ($this->alreadyNotified($buyer, PaymentOverdue::class, $rule->id, $installment->id, $rule->repeat_every_days)) {
                continue;
            }

            $buyer->notify(new PaymentOverdue($installment, $rule));
            $sent++;
        }

        return $sent;
    }

    protected function isReminderEligible(Installment $installment): bool
    {
        return ! $installment->isPaid() && ! $installment->sale->isCompleted();
    }

    /**
     * Not rule-driven, unlike the two reminder types above - this fires
     * once, the moment a sale's balance actually reaches zero, so it
     * stays a one-off event notice rather than a configurable cadence.
     */
    protected function sendCompletedSaleNotices(Company $company): int
    {
        $completedSales = Sale::query()
            ->forCompany($company->id)
            ->where('status', 'active')
            ->whereHas('installments', fn ($query) => $query->where('status', '!=', 'paid'))
            ->get()
            ->filter(fn (Sale $sale) => $sale->remaining_balance <= 0);

        $sent = 0;

        foreach ($completedSales as $sale) {
            if (! $sale->buyer || $this->alreadyNotified($sale->buyer, SaleCompleted::class, null, null, saleId: $sale->id)) {
                continue;
            }

            $sale->markAsCompleted();
            $sale->buyer->notify(new SaleCompleted($sale));
            $sent++;

            $landlord = $sale->unit->building->property->landlord;

            if ($landlord) {
                $landlord->notify(new SaleCompleted($sale));
            }
        }

        return $sent;
    }

    protected function alreadyNotified(Model $notifiable, string $notificationClass, ?int $ruleId, ?int $installmentId, ?int $withinDays = null, ?int $saleId = null): bool
    {
        return $notifiable->notifications()
            ->where('type', $notificationClass)
            ->when($installmentId !== null, fn ($query) => $query
                ->where('data->installment_id', $installmentId)
                ->where('data->rule_id', $ruleId))
            ->when($saleId !== null, fn ($query) => $query->where('data->sale_id', $saleId))
            ->when($withinDays, fn ($query) => $query->where('created_at', '>=', now()->subDays($withinDays)))
            ->exists();
    }
}
