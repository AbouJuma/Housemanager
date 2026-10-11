<?php

namespace App\Modules\PaymentReminders\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PaymentReminders\Http\Requests\StoreReminderRuleRequest;
use App\Modules\PaymentReminders\Http\Requests\UpdateReminderRuleRequest;
use App\Modules\PaymentReminders\Models\PaymentReminderRule;
use App\Modules\PaymentReminders\Services\ReminderRuleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * No Policy/per-model authorization - see PaymentRemindersServiceProvider's
 * docblock. Every action is gated by the single `reminder-rules.manage`
 * permission (StoreReminderRuleRequest/UpdateReminderRuleRequest check
 * it for store/update; index/destroy check it directly here).
 */
class ReminderRuleController extends Controller
{
    public function __construct(protected ReminderRuleService $service)
    {
    }

    public function index(Request $request): View
    {
        $this->authorize('reminder-rules.manage');

        $company = $request->user()->company;

        return view('payment-reminders::index', [
            'rentRules' => $this->service->rulesFor($company, PaymentReminderRule::CONTEXT_RENT),
            'saleInstallmentRules' => $this->service->rulesFor($company, PaymentReminderRule::CONTEXT_SALE_INSTALLMENT),
        ]);
    }

    public function store(StoreReminderRuleRequest $request): RedirectResponse
    {
        $this->service->create($request->user()->company, $request->validated());

        return redirect()->route('reminder-rules.index')->with('success', 'Reminder rule added.');
    }

    public function update(UpdateReminderRuleRequest $request, PaymentReminderRule $reminderRule): RedirectResponse
    {
        $this->service->update($reminderRule, $request->validated());

        return redirect()->route('reminder-rules.index')->with('success', 'Reminder rule updated.');
    }

    public function destroy(PaymentReminderRule $reminderRule): RedirectResponse
    {
        $this->authorize('reminder-rules.manage');

        $this->service->delete($reminderRule);

        return redirect()->route('reminder-rules.index')->with('success', 'Reminder rule removed.');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $this->authorize('reminder-rules.manage');

        $request->validate([
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['integer'],
        ]);

        $this->service->reorder($request->user()->company, $request->input('ordered_ids'));

        return redirect()->route('reminder-rules.index')->with('success', 'Reminder rules reordered.');
    }
}
