<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Repositories\JournalEntryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Deliberately does NOT extend BaseService - update()/delete() have no
 * meaning here (a posted journal entry is immutable; correcting one
 * means void(), which posts a real reversing entry, never edits or
 * removes history - the same "no editing past a lifecycle point"
 * discipline as Invoice's editable-only-while-draft rule,
 * ARCHITECTURE.md §10e/§12), so inheriting those methods would just be
 * dead surface a caller could wrongly reach for.
 */
class JournalEntryService
{
    public function __construct(protected JournalEntryRepositoryInterface $repository)
    {
    }

    public function paginate(?string $search, ?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            // Single query per sum column across the whole page, not
            // a lazy-loaded lines relation per row - JournalEntry::
            // totalDebits() reads this directly when present (same
            // pattern as Account::balance(), ARCHITECTURE.md §12).
            ->withSum('lines as debit_total', 'debit')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('description', 'like', "%{$search}%")->orWhere('reference', 'like', "%{$search}%");
            }))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest('entry_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * $attributes['lines'] is an array of {account_id, debit, credit,
     * memo} - not persisted columns on `journal_entries` itself, peeled
     * off before reaching the repository, mirroring how
     * InvoiceService::create() handles 'items'.
     */
    public function post(array $attributes): Model
    {
        $lines = $attributes['lines'] ?? [];
        unset($attributes['lines']);

        $this->assertBalanced($lines);

        return DB::transaction(function () use ($attributes, $lines) {
            $entry = $this->repository->create($attributes);
            $this->createLines($entry, $lines);

            return $entry->fresh('lines');
        });
    }

    /**
     * Never deletes or edits the original entry - posts a real
     * reversing entry (every line's debit/credit swapped) and marks
     * the original 'voided'. This is standard accounting practice
     * (ledger history is never erased) and means Account::balance()
     * doesn't need to filter by status at all - the reversal already
     * nets the original to zero on its own.
     */
    public function void(JournalEntry $entry, ?string $reason = null): JournalEntry
    {
        if ($entry->isVoided()) {
            throw ValidationException::withMessages(['status' => 'This journal entry is already voided.']);
        }

        return DB::transaction(function () use ($entry, $reason) {
            $reversal = $this->repository->create([
                'company_id' => $entry->company_id,
                'entry_date' => now()->toDateString(),
                'reference' => $entry->reference,
                'description' => 'Reversal of: '.$entry->description.($reason ? " ({$reason})" : ''),
                'source_type' => $entry->source_type,
                'source_id' => $entry->source_id,
            ]);

            $this->createLines($reversal, $entry->lines->map(fn ($line) => [
                'account_id' => $line->account_id,
                'debit' => $line->credit,
                'credit' => $line->debit,
                'memo' => $line->memo,
            ])->all());

            $entry->update(['status' => 'voided']);

            return $entry->fresh('lines');
        });
    }

    protected function createLines(JournalEntry $entry, array $lines): void
    {
        foreach ($lines as $line) {
            $entry->lines()->create([
                'company_id' => $entry->company_id,
                'account_id' => $line['account_id'],
                'debit' => $line['debit'] ?? 0,
                'credit' => $line['credit'] ?? 0,
                'memo' => $line['memo'] ?? null,
            ]);
        }
    }

    protected function assertBalanced(array $lines): void
    {
        $totalDebits = round(array_sum(array_map(fn ($line) => (float) ($line['debit'] ?? 0), $lines)), 2);
        $totalCredits = round(array_sum(array_map(fn ($line) => (float) ($line['credit'] ?? 0), $lines)), 2);

        if ($totalDebits <= 0 || $totalDebits !== $totalCredits) {
            throw ValidationException::withMessages(['lines' => 'Debits and credits must balance and total more than zero.']);
        }
    }
}
