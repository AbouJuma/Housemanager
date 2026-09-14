<?php

namespace App\Modules\Maintenance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordMaintenanceExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('maintenance_request'));
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('company_id', $this->user()->company_id)->where('type', 'expense')],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'payment_status' => ['required', Rule::in(['unpaid', 'paid'])],
            'payment_method' => ['required_if:payment_status,paid', 'nullable', Rule::in(['cash', 'bank_transfer', 'cheque', 'mobile_money', 'other'])],
            'paid_on' => ['required_if:payment_status,paid', 'nullable', 'date'],
        ];
    }
}
