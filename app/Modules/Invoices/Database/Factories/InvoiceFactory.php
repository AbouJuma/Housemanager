<?php

namespace App\Modules\Invoices\Database\Factories;

use App\Modules\Invoices\Models\Invoice;
use App\Modules\Leases\Models\Lease;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $total = fake()->randomFloat(2, 100, 3000);

        return [
            'lease_id' => Lease::factory(),
            // Bare factory use (mostly tests) doesn't go through
            // InvoiceService::generateInvoiceNumber() - unique() here
            // is stricter than the DB's (company_id, invoice_number)
            // constraint actually requires, but that's a safe
            // over-guarantee, not a bug.
            'invoice_number' => 'INV-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'draft',
            'total_amount' => $total,
            'amount_paid' => 0,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn () => ['status' => 'sent']);
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => now()->subDays(10)->toDateString(),
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'amount_paid' => $attributes['total_amount'],
        ]);
    }

    public function partiallyPaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'partially_paid',
            'amount_paid' => round(((float) $attributes['total_amount']) / 2, 2),
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => 'cancelled']);
    }
}
