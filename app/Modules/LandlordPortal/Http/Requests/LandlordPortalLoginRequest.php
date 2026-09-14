<?php

namespace App\Modules\LandlordPortal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LandlordPortalLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
