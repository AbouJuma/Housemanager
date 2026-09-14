<?php

namespace App\Modules\TenantPortal\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class PortalPayInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $tenant = Auth::guard('tenant')->user();
        $invoice = $this->route('invoice');

        return $tenant !== null && $invoice->lease->tenant_id === $tenant->id;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
