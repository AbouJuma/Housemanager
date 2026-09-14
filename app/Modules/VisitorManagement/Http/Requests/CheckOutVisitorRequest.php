<?php

namespace App\Modules\VisitorManagement\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckOutVisitorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('visitor'));
    }

    public function rules(): array
    {
        return [
            'check_out_at' => ['nullable', 'date'],
        ];
    }
}
