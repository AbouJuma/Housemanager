<?php

use App\Modules\RentCollection\Http\Controllers\Api\V1\PaymentWebhookController;
use App\Modules\RentCollection\Http\Controllers\Api\V1\RentCollectionController;
use Illuminate\Support\Facades\Route;

Route::middleware('api-tenant')->group(function (): void {
    Route::post('invoices/{invoice}/charge', [RentCollectionController::class, 'charge'])->name('api.v1.invoices.charge');
    Route::post('invoices/{invoice}/payment-attempts/{attempt}/verify', [RentCollectionController::class, 'verify'])->name('api.v1.invoices.payment-attempts.verify');
});

// Inbound from payment gateways, not from an authenticated app user -
// no Sanctum token exists to require here. Throttled per ARCHITECTURE.md
// §7's "rate limiting on auth, OTP, and payment webhook endpoints"
// requirement; see PaymentWebhookController's docblock for why it's
// still safe without per-provider signature verification.
Route::post('webhooks/payments/{provider}', [PaymentWebhookController::class, 'handle'])
    ->middleware('throttle:60,1')
    ->name('api.v1.webhooks.payments');
