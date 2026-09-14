<?php

namespace App\Modules\Expenses\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MarkExpensePaidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('expense'));
    }

    public function rules(): array
    {
        return [
            'payment_method' => ['required', Rule::in(['cash', 'bank_transfer', 'cheque', 'mobile_money', 'other'])],
            'paid_on' => ['required', 'date'],
        ];
    }
}
