<?php

namespace App\Modules\Invoices\Events;

use App\Modules\Invoices\Models\Invoice;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired by InvoiceService::send(). Invoices has no idea who (if
 * anyone) listens - Accounting's revenue-recognition posting
 * (ARCHITECTURE.md §12) is the first listener, but this module stays
 * decoupled from it the same way Companies\Events\CompanyCreated
 * stays decoupled from Users\Listeners\SeedDefaultRolesForCompany.
 */
class InvoiceSent
{
    use Dispatchable;

    public function __construct(public readonly Invoice $invoice)
    {
    }
}
