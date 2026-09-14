<?php

namespace App\Modules\Expenses\Http\Requests;

use App\Modules\Expenses\Models\Expense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Expense::class);
    }

    public function rules(): array
    {
        return [
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('company_id', $this->user()->company_id)->where('type', 'expense')],
            'property_id' => ['nullable', Rule::exists('properties', 'id')->where('company_id', $this->user()->company_id)],
            // Optional formal vendor link - vendor_name (free text)
            // stays available alongside it for one-off vendors not
            // worth adding as a full record, see ARCHITECTURE.md §14.
            'vendor_id' => ['nullable', Rule::exists('vendors', 'id')->where('company_id', $this->user()->company_id)],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date'],
            'payment_status' => ['required', Rule::in(['unpaid', 'paid'])],
            'payment_method' => ['required_if:payment_status,paid', 'nullable', Rule::in(['cash', 'bank_transfer', 'cheque', 'mobile_money', 'other'])],
            'paid_on' => ['required_if:payment_status,paid', 'nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
