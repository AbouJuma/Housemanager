<?php

namespace App\Modules\Invoices\Events;

use App\Modules\Invoices\Models\InvoicePayment;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired by InvoiceService::recordPayment() - see InvoiceSent's
 * docblock for why Invoices dispatches this without knowing who
 * listens (ARCHITECTURE.md §12).
 */
class InvoicePaymentRecorded
{
    use Dispatchable;

    public function __construct(public readonly InvoicePayment $payment)
    {
    }
}
