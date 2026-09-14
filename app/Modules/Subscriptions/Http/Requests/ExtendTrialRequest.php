<?php

namespace App\Modules\Subscriptions\Http\Requests;

use App\Modules\Subscriptions\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;

class ExtendTrialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('subscription') ?? new Subscription());
    }

    public function rules(): array
    {
        return [
            'days' => ['required', 'integer', 'min:1', 'max:365'],
        ];
    }
}
