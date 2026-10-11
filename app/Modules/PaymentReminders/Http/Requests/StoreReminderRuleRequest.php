<?php

namespace App\Modules\PaymentReminders\Http\Requests;

use App\Modules\PaymentReminders\Models\PaymentReminderRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReminderRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reminder-rules.manage');
    }

    public function rules(): array
    {
        return [
            'context' => ['required', Rule::in([PaymentReminderRule::CONTEXT_RENT, PaymentReminderRule::CONTEXT_SALE_INSTALLMENT])],
            'offset_days' => ['required', 'integer', 'between:-365,365'],
            'repeat_every_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_active' => ['boolean'],
        ];
    }
}
