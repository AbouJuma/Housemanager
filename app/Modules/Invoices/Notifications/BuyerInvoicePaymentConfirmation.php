<?php

namespace App\Modules\Invoices\Notifications;

use App\Notifications\TemplatedNotification;

/**
 * Sent to a buyer once a payment is recorded against their purchase -
 * fired from two places: App\Modules\Invoices\Services\InvoiceService::
 * recordPayment() (a sale-linked Invoice paid directly) and
 * App\Modules\Sales\Services\SaleService::recordPayment() (an
 * installment paid, which also generates its own Invoice - see
 * SaleService::createPaymentInvoice()). Both now use the `buyer_invoice`
 * template type - SaleService previously mislabeled this under
 * `tenant_payment_notification`, which is corrected here. Expected
 * $data placeholders: amount, unit_number, property_name, payment_date,
 * balance, payment_terms, invoice_number.
 */
class BuyerInvoicePaymentConfirmation extends TemplatedNotification
{
    public function __construct(array $data)
    {
        parent::__construct('buyer_invoice', $data);
    }

    protected function title(): string
    {
        return 'Payment confirmation sent';
    }

    protected function summary(mixed $notifiable): string
    {
        return "Payment confirmation sent to {$notifiable->name}.";
    }
}
