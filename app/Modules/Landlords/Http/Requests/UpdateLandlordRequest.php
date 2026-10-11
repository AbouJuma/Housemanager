<?php

namespace App\Modules\Landlords\Http\Requests;

use App\Modules\Landlords\Models\Landlord;
use App\Core\Auth\UsernameRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLandlordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('landlord'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Optional - generated from the name when blank.
            'username' => UsernameRules::for(Landlord::class, $this->route('landlord')?->id),
            'type' => ['required', Rule::in(['individual', 'company'])],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'secondary_phone' => ['nullable', 'string', 'max:32'],
            'address_line1' => ['nullable', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country_code' => ['nullable', 'string', 'size:2'],
            'tax_id' => ['nullable', 'string', 'max:64'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:64'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'bank_swift_code' => ['nullable', 'string', 'max:32'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return UsernameRules::messages();
    }
}
