<?php

namespace App\Modules\Documents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * documentable_type/documentable_id are deliberately absent - what a
 * document is attached to is fixed at upload time, the same "locked
 * after creation" discipline as Lease's tenant_id/unit_id
 * (ARCHITECTURE.md §10d), since re-pointing a document at a different
 * entity isn't a real workflow this app needs to support. `file` is
 * optional here (unlike Store) - only replaced if a new one is sent,
 * the same shape as CompanyService::updateBranding()'s logo.
 */
class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('document'));
    }

    public function rules(): array
    {
        $categories = ['contract', 'lease_agreement', 'id_document', 'certificate', 'insurance', 'deed', 'invoice', 'license', 'other'];

        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in($categories)],
            'description' => ['nullable', 'string'],
            'expiry_date' => ['nullable', 'date'],
            'file' => ['nullable', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
        ];
    }
}
