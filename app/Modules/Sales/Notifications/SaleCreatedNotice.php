<?php

namespace App\Modules\Sales\Notifications;

use App\Notifications\TemplatedNotification;

/**
 * The single message a buyer gets when a sale is created - price, what
 * they've paid, what's pending and the installment plan - instead of one
 * "new bill" text per installment. Template type `sale_created`; $data
 * comes from App\Modules\Sales\Models\Sale::messagePlaceholders().
 */
class SaleCreatedNotice extends TemplatedNotification
{
    public function __construct(array $data)
    {
        parent::__construct('sale_created', $data);
    }

    protected function title(): string
    {
        return 'Sale details sent';
    }

    protected function summary(mixed $notifiable): string
    {
        return "Purchase details sent to {$notifiable->name}.";
    }
}
