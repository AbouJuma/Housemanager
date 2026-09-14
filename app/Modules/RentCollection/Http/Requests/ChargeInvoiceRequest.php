<?php

namespace App\Modules\RentCollection\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChargeInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('invoice'));
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
