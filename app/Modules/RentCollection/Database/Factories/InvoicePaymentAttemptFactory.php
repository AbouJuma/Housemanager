<?php

namespace App\Modules\RentCollection\Database\Factories;

use App\Modules\Invoices\Models\Invoice;
use App\Modules\RentCollection\Models\InvoicePaymentAttempt;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<InvoicePaymentAttempt>
 */
class InvoicePaymentAttemptFactory extends Factory
{
    protected $model = InvoicePaymentAttempt::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'provider' => fake()->randomElement(['stripe', 'mpesa', 'selcom', 'flutterwave']),
            'reference' => 'INV-'.Str::upper(Str::random(10)),
            'amount_minor' => fake()->numberBetween(10000, 300000),
            'currency' => 'USD',
            'status' => 'pending',
            'message' => null,
        ];
    }

    public function successful(): static
    {
        return $this->state(fn () => ['status' => 'successful', 'verified_at' => now()]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => 'failed', 'message' => 'The customer cancelled the payment.', 'verified_at' => now()]);
    }
}
