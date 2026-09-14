<?php

use App\Modules\Subscriptions\Http\Controllers\BillingController;
use App\Modules\Subscriptions\Http\Controllers\CompanySubscriptionController;
use App\Modules\Subscriptions\Http\Controllers\SubscriptionInvoiceController;
use App\Modules\Subscriptions\Http\Controllers\SubscriptionPlanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth'])->group(function (): void {
    // SaaS-owner: the subscription plan catalog.
    Route::resource('subscription-plans', SubscriptionPlanController::class);

    // SaaS-owner: state changes on a company's own subscription, wired
    // into Company's own show page (Module 2).
    Route::post('companies/{company}/subscription/change-plan', [CompanySubscriptionController::class, 'changePlan'])
        ->name('companies.subscription.change-plan');
    Route::post('companies/{company}/subscription/reactivate', [CompanySubscriptionController::class, 'reactivate'])
        ->name('companies.subscription.reactivate');
    Route::post('subscriptions/{subscription}/extend-trial', [CompanySubscriptionController::class, 'extendTrial'])
        ->name('subscriptions.extend-trial');
    Route::post('subscriptions/{subscription}/renew', [CompanySubscriptionController::class, 'renew'])
        ->name('subscriptions.renew');
    Route::post('subscriptions/{subscription}/cancel', [CompanySubscriptionController::class, 'cancel'])
        ->name('subscriptions.cancel');

    // SaaS-owner: platform billing.
    Route::post('subscriptions/{subscription}/invoices/generate', [SubscriptionInvoiceController::class, 'generate'])
        ->name('subscriptions.invoices.generate');
    Route::post('subscription-invoices/{subscription_invoice}/mark-paid', [SubscriptionInvoiceController::class, 'markPaid'])
        ->name('subscription-invoices.mark-paid');
    Route::post('subscription-invoices/{subscription_invoice}/void', [SubscriptionInvoiceController::class, 'void'])
        ->name('subscription-invoices.void');

    // Company-facing: read-only, own plan + billing history.
    Route::get('billing', [BillingController::class, 'index'])->name('billing.index');
});
