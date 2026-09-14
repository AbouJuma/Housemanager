<?php

namespace App\Modules\Accounting\Http\Requests;

use App\Modules\Accounting\Models\JournalEntry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreJournalEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', JournalEntry::class);
    }

    public function rules(): array
    {
        return [
            'entry_date' => ['required', 'date'],
            'reference' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'lines' => ['required', 'array', 'min:2'],
            'lines.*.account_id' => ['required', Rule::exists('accounts', 'id')->where('company_id', $this->user()->company_id)],
            'lines.*.debit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.credit' => ['nullable', 'numeric', 'min:0'],
            'lines.*.memo' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * A single line being both a debit and a credit (or neither) isn't
     * a real ledger line - the overall-balance check in
     * JournalEntryService::post() catches a mismatched total, but not
     * this per-line shape problem, so it's caught here instead.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('lines', []) as $index => $line) {
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                if ($debit > 0 && $credit > 0) {
                    $validator->errors()->add("lines.{$index}", 'A line cannot be both a debit and a credit.');
                }

                if ($debit <= 0 && $credit <= 0) {
                    $validator->errors()->add("lines.{$index}", 'Each line needs a debit or a credit amount.');
                }
            }
        });
    }
}
