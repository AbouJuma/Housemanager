<?php

namespace App\Modules\Invoices\Http\Requests;

use App\Core\Http\Concerns\ResolvesUuidReferences;
use App\Modules\Invoices\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreInvoiceRequest extends FormRequest
{
    use ResolvesUuidReferences;

    public function authorize(): bool
    {
        return $this->user()->can('create', Invoice::class);
    }

    /**
     * See App\Core\Http\Concerns\ResolvesUuidReferences - lease_id
     * arrives as a numeric id from the web form's <select>, or a uuid
     * from a JSON client (LeaseResource never exposes the numeric id).
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['lease_id' => $this->resolveUuidReference('leases', $this->input('lease_id'))]);
    }

    public function rules(): array
    {
        return [
            // Scoped to the acting user's own company, same discipline
            // as leases.tenant_id/unit_id (ARCHITECTURE.md §10d).
            'lease_id' => ['required', Rule::exists('leases', 'id')->where('company_id', $this->user()->company_id)],
            'issue_date' => ['required', 'date'],
            'due_date' => ['required', 'date', 'after_or_equal:issue_date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.01'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ];
    }
}
