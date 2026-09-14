<?php

namespace App\Modules\Expenses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('expense'));
    }

    /**
     * account_id/amount/expense_date/payment_status are deliberately
     * not accepted here - once an expense has posted its initial
     * journal entry (always, at creation), changing anything that
     * would desync from that ledger history means voiding the expense
     * and creating a corrected one, not editing it (ARCHITECTURE.md
     * §13). Only metadata - not what actually happened financially -
     * is editable after the fact.
     */
    public function rules(): array
    {
        return [
            'property_id' => ['nullable', Rule::exists('properties', 'id')->where('company_id', $this->user()->company_id)],
            'vendor_id' => ['nullable', Rule::exists('vendors', 'id')->where('company_id', $this->user()->company_id)],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
