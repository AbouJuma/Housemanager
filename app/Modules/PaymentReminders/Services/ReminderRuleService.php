<?php

namespace App\Modules\PaymentReminders\Services;

use App\Modules\Companies\Models\Company;
use App\Modules\PaymentReminders\Models\PaymentReminderRule;
use Illuminate\Database\Eloquent\Collection;

class ReminderRuleService
{
    /**
     * @return Collection<int, PaymentReminderRule>
     */
    public function rulesFor(Company $company, string $context): Collection
    {
        return PaymentReminderRule::query()
            ->forCompany($company->id)
            ->context($context)
            ->active()
            ->ordered()
            ->get();
    }

    public function create(Company $company, array $attributes): PaymentReminderRule
    {
        return PaymentReminderRule::create([
            ...$attributes,
            'company_id' => $company->id,
        ]);
    }

    public function update(PaymentReminderRule $rule, array $attributes): PaymentReminderRule
    {
        $rule->update($attributes);

        return $rule;
    }

    public function delete(PaymentReminderRule $rule): void
    {
        $rule->delete();
    }

    /**
     * @param  array<int, int>  $orderedIds  rule IDs in their new display order
     */
    public function reorder(Company $company, array $orderedIds): void
    {
        foreach (array_values($orderedIds) as $index => $id) {
            PaymentReminderRule::query()
                ->forCompany($company->id)
                ->where('id', $id)
                ->update(['sort_order' => $index]);
        }
    }

    /**
     * The exact defaults every hardcoded reminder command used before
     * this rule engine existed - preserves current behavior for a
     * brand-new company (via SeedDefaultReminderRulesForCompany) and for
     * existing companies backfilled by
     * payment-reminders:backfill-default-rules.
     *
     * @return array<int, array{context: string, offset_days: int, repeat_every_days: ?int, sort_order: int}>
     */
    public static function defaultRuleDefinitions(): array
    {
        return [
            ['context' => PaymentReminderRule::CONTEXT_RENT, 'offset_days' => 3, 'repeat_every_days' => null, 'sort_order' => 0],
            // offset_days must be NEGATIVE here, not 0 - PaymentReminderRule::isBeforeOrOnDueDate()
            // (offset_days >= 0) is what routes a rule to the due-soon vs
            // overdue branch in SendRentReminders/SendPaymentReminders;
            // 0 means "due today, not yet overdue" and belongs to the
            // due-soon branch above.
            ['context' => PaymentReminderRule::CONTEXT_RENT, 'offset_days' => -1, 'repeat_every_days' => 7, 'sort_order' => 1],
            ['context' => PaymentReminderRule::CONTEXT_SALE_INSTALLMENT, 'offset_days' => 7, 'repeat_every_days' => null, 'sort_order' => 0],
            ['context' => PaymentReminderRule::CONTEXT_SALE_INSTALLMENT, 'offset_days' => -1, 'repeat_every_days' => 30, 'sort_order' => 1],
        ];
    }

    public function seedDefaultsFor(Company $company): void
    {
        foreach (self::defaultRuleDefinitions() as $definition) {
            // forCompany() explicitly bypasses BelongsToCompany's
            // ambient-CompanyContext global scope for the SELECT half
            // of firstOrCreate (withoutGlobalScope + an explicit
            // company_id match) - the same discipline
            // App\Modules\Users\Listeners\SeedDefaultRolesForCompany
            // uses via PermissionsTeam::runAs(), since this listener can
            // run with no request-scoped company context active at all.
            // company_id must ALSO stay in the first array - it's not
            // just a query filter, it's part of what gets inserted if
            // no match is found; forCompany()'s where() clause has no
            // bearing on that.
            PaymentReminderRule::query()->forCompany($company->id)->firstOrCreate([
                'company_id' => $company->id,
                'context' => $definition['context'],
                'offset_days' => $definition['offset_days'],
            ], [
                'repeat_every_days' => $definition['repeat_every_days'],
                'is_active' => true,
                'sort_order' => $definition['sort_order'],
            ]);
        }
    }
}
