<?php

namespace App\Modules\Documents\Http\Requests;

use App\Modules\Documents\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Document::class);
    }

    public function rules(): array
    {
        $categories = ['contract', 'lease_agreement', 'id_document', 'certificate', 'insurance', 'deed', 'invoice', 'license', 'other'];
        $typeMap = Document::documentableTypeMap();

        return [
            'documentable_type' => ['nullable', Rule::in(array_keys($typeMap))],
            'documentable_id' => [
                'nullable',
                'required_with:documentable_type',
                $this->documentableExistsRule($typeMap),
            ],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in($categories)],
            'description' => ['nullable', 'string'],
            'expiry_date' => ['nullable', 'date'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png', 'max:10240'],
        ];
    }

    /**
     * @param  array<string, class-string>  $typeMap
     */
    protected function documentableExistsRule(array $typeMap): Exists|string
    {
        $class = $typeMap[$this->input('documentable_type')] ?? null;

        if (! $class) {
            return 'nullable';
        }

        return Rule::exists((new $class())->getTable(), 'id')->where('company_id', $this->user()->company_id);
    }
}
