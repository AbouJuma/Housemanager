<?php

namespace App\Modules\PaymentReminders\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReminderRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('reminder-rules.manage');
    }

    public function rules(): array
    {
        return [
            'offset_days' => ['required', 'integer', 'between:-365,365'],
            'repeat_every_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'is_active' => ['boolean'],
        ];
    }
}
