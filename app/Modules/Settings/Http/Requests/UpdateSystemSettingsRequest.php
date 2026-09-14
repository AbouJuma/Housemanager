<?php

namespace App\Modules\Settings\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSystemSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('settings.update-system');
    }

    public function rules(): array
    {
        return [
            'default_trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'default_currency_code' => ['required', 'string', 'size:3'],
        ];
    }
}
