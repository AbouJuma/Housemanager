<?php

namespace App\Modules\Companies\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A company admin editing their own company's details (Settings ->
 * Company Profile). Deliberately narrower than UpdateCompanyRequest:
 * slug and status stay with the SaaS owner.
 */
class UpdateCompanyProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.update-company');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'website' => ['nullable', 'url', 'max:255'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'timezone' => ['required', 'timezone'],
            'currency_code' => ['required', 'string', 'size:3', Rule::in(array_keys(config('company.currencies')))],
        ];
    }

    public function messages(): array
    {
        return [
            'website.url' => 'Enter the full website address, starting with https://',
        ];
    }
}
