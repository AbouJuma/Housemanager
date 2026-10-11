<?php

namespace App\Modules\Invoices\Notifications;

use App\Notifications\TemplatedNotification;

/**
 * Sent to a landlord when a buyer pays an installment on one of their
 * units - which installment, how many remain and what's left to pay.
 * Separate from LandlordPaymentConfirmation (rent payments) because the
 * placeholders differ. Template type `sale_payment_landlord`; $data is
 * Sale::messagePlaceholders() plus amount, payment_date,
 * installment_number and installment_balance.
 */
class LandlordSalePaymentConfirmation extends TemplatedNotification
{
    public function __construct(array $data)
    {
        parent::__construct('sale_payment_landlord', $data);
    }

    protected function title(): string
    {
        return 'Sale payment notification sent';
    }

    protected function summary(mixed $notifiable): string
    {
        return "Sale payment notification sent to {$notifiable->name}.";
    }
}
