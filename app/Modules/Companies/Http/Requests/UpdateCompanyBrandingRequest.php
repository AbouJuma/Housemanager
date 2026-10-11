<?php

namespace App\Modules\Companies\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanyBrandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        // companies/{company}/branding (SaaS owner / company page) goes
        // through the company policy; the company admin's own
        // Settings -> Branding screen has no {company} and uses the
        // company-settings permission instead.
        $company = $this->route('company');

        return $company
            ? $this->user()->can('update', $company)
            : $this->user()->can('settings.update-company');
    }

    public function rules(): array
    {
        return [
            'logo' => ['nullable', 'image', 'max:2048'],
            'primary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'secondary_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'remove_logo' => ['nullable', 'boolean'],
            'reset' => ['nullable', 'boolean'],
        ];
    }
}
