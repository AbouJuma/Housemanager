<?php

namespace App\Modules\BuyerPortal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BuyerPortalLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Email, username or phone number - see App\Core\Auth\LoginIdentity.
            'email' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }
}
