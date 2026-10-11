<?php

namespace App\Modules\Sales\Http\Requests;

use App\Core\Auth\UsernameRules;
use App\Modules\Sales\Models\Buyer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBuyerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('buyer'));
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Optional - generated from the name when blank.
            'username' => UsernameRules::for(Buyer::class, $this->route('buyer')?->id),
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:500'],
            'id_number' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return UsernameRules::messages();
    }
}
