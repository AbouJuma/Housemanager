<?php

namespace App\Modules\Users\Http\Requests;

use App\Core\Auth\UsernameRules;
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
            // Optional - generated from the name when blank.
            'username' => UsernameRules::for(User::class, null),
            // Optional - staff can sign in with their username or phone instead.
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20'],
            // Blank = their phone number (App\Core\Auth\LoginIdentity::defaultPassword).
            'password' => ['nullable', Password::defaults()],
            'company_id' => $this->user()->isSaasOwner()
                ? ['nullable', 'integer', 'exists:companies,id']
                : ['prohibited'],
            'roles' => ['nullable', 'array'],
            'roles.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return UsernameRules::messages();
    }
}
