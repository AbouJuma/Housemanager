<?php

namespace App\Modules\Accounting\Database\Factories;

use App\Modules\Accounting\Models\JournalEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JournalEntry>
 *
 * Bare factory use (mostly tests) doesn't create matching lines - a
 * balanced entry needs its lines created deliberately (see
 * JournalEntryService::post(), which real code always goes through).
 */
class JournalEntryFactory extends Factory
{
    protected $model = JournalEntry::class;

    public function definition(): array
    {
        return [
            'entry_date' => now()->toDateString(),
            'description' => 'Manual adjustment',
            'status' => 'posted',
        ];
    }

    public function voided(): static
    {
        return $this->state(fn () => ['status' => 'voided']);
    }
}
