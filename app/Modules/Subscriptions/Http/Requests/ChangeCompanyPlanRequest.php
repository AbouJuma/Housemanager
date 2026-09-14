<?php

namespace App\Modules\Subscriptions\Http\Requests;

use App\Modules\Subscriptions\Models\Subscription;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeCompanyPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Subscription::class);
    }

    public function rules(): array
    {
        return [
            'subscription_plan_id' => ['required', Rule::exists('subscription_plans', 'id')->where('is_active', true)],
            'status' => ['required', Rule::in(['trialing', 'active'])],
        ];
    }
}
