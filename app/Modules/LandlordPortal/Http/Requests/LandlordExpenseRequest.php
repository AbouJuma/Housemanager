<?php

namespace App\Modules\LandlordPortal\Http\Requests;

use App\Modules\LandlordPortal\Support\LandlordAccess;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A landlord recording an expense on one of their properties, or editing
 * one. Like staff, the amount, date and payment can't change once saved
 * (it's already in the books) - only the details.
 */
class LandlordExpenseRequest extends FormRequest
{
    public const METHODS = ['cash', 'bank_transfer', 'cheque', 'mobile_money', 'other'];

    public function authorize(): bool
    {
        $expense = $this->route('expense');

        if ($expense) {
            return same_id($expense->property?->landlord_id, $this->user()?->id)
                && $expense->status !== 'voided'
                && LandlordAccess::can($this->user(), 'expenses.update');
        }

        return LandlordAccess::can($this->user(), 'expenses.create');
    }

    public function rules(): array
    {
        $landlord = $this->user();
        $ownProperty = Rule::exists('properties', 'id')->where('landlord_id', $landlord->id)->whereNull('deleted_at');

        if ($this->route('expense')) {
            return [
                'property_id' => ['required', $ownProperty],
                'vendor_name' => ['nullable', 'string', 'max:255'],
                'description' => ['required', 'string', 'max:255'],
                'reference' => ['nullable', 'string', 'max:255'],
                'notes' => ['nullable', 'string', 'max:5000'],
            ];
        }

        return [
            'property_id' => ['required', $ownProperty],
            'account_id' => ['required', Rule::exists('accounts', 'id')->where('company_id', $landlord->company_id)->where('type', 'expense')],
            'vendor_name' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['required', 'date', 'before_or_equal:today'],
            'payment_status' => ['required', Rule::in(['unpaid', 'paid'])],
            'payment_method' => ['required_if:payment_status,paid', 'nullable', Rule::in(self::METHODS)],
            'paid_on' => ['required_if:payment_status,paid', 'nullable', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return ['property_id' => __('property'), 'account_id' => __('category')];
    }
}
