<?php

namespace App\Modules\Users\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', Password::defaults()],
            'company_id' => $this->user()->isSaasOwner()
                ? ['nullable', 'integer', 'exists:companies,id']
                : ['prohibited'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
        ];
    }
}
