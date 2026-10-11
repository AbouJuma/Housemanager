<?php

namespace App\Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.update-company');
    }

    public function rules(): array
    {
        return [
            'date_format' => ['required', 'string', 'max:255'],
            'payment_details_source' => ['sometimes', 'in:company,landlord'],
            'company_bank_name' => ['nullable', 'string', 'max:255'],
            'company_bank_account_name' => ['nullable', 'string', 'max:255'],
            'company_bank_account_number' => ['nullable', 'string', 'max:255'],
        ];
    }
}
