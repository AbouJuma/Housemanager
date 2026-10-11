<?php

namespace App\Modules\Sales\Notifications;

use App\Notifications\TemplatedNotification;

/**
 * Sent once, when a Buyer record is created - see
 * App\Modules\Sales\Http\Controllers\BuyerController::store(), the only
 * place a Buyer is ever created (a Sale always references an existing
 * buyer via new_buyer_id, picked from a dropdown - see
 * resources/views/create.blade.php - so SaleService::create() never
 * creates one and must not re-send this). A new buyer has no purchase
 * yet, so only buyer_name, company_name, phone and control_number are
 * offered; the purchase itself is announced by SaleCreatedNotice.
 */
class BuyerWelcome extends TemplatedNotification
{
    public function __construct(array $data)
    {
        parent::__construct('buyer_welcome', $data);
    }

    protected function title(): string
    {
        return 'Welcome message sent';
    }

    protected function summary(mixed $notifiable): string
    {
        return "Welcome message sent to {$notifiable->name}.";
    }
}
