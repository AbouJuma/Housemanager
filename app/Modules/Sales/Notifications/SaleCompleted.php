<?php

namespace App\Modules\Sales\Notifications;

use App\Modules\Sales\Models\Sale;
use App\Notifications\TemplatedNotification;

/**
 * Sent to both the buyer and the unit's landlord once every installment
 * on a sale is paid off - see
 * App\Modules\Sales\Console\Commands\SendPaymentReminders. Text comes
 * from the `sale_completed` MessageTemplate (via the
 * TemplatedNotification base) rather than being hardcoded - the
 * wording is deliberately recipient-neutral (names the buyer rather
 * than addressing them directly) since the exact same rendered message
 * goes to both the buyer and their landlord.
 */
class SaleCompleted extends TemplatedNotification
{
    public function __construct(protected Sale $sale)
    {
        // Same figures every other sale message uses (paid comes from the
        // sale's invoices), so this text never disagrees with the
        // per-payment messages that came before it.
        parent::__construct('sale_completed', $sale->messagePlaceholders());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->summary($notifiable),
            'url' => url('/sales/'.$this->sale->uuid),
            'sale_id' => $this->sale->id,
        ];
    }

    protected function title(): string
    {
        return 'Sale completed';
    }

    protected function summary(mixed $notifiable): string
    {
        return "Sale for unit {$this->sale->unit->unit_number} has been completed. Total paid: ".number_format($this->sale->total_paid, 2);
    }
}
