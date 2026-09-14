<?php

namespace App\Modules\Sales\Notifications;

use App\Modules\Sales\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SaleCompleted extends Notification
{
    use Queueable;

    public function __construct(protected Sale $sale)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        $unitInfo = $this->sale->unit->unit_number . ' · ' . $this->sale->unit->building->name;
        
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Sale Completed - ' . $unitInfo)
            ->greeting('Congratulations!')
            ->line('The sale has been completed successfully.')
            ->line('Unit: ' . $unitInfo)
            ->line('Total Price: ' . number_format($this->sale->total_price, 2))
            ->line('Total Paid: ' . number_format($this->sale->total_paid, 2))
            ->line('All installments have been paid.')
            ->action('View Sale Details', url('/sales/' . $this->sale->uuid))
            ->line('Thank you for your business!');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Sale Completed',
            'message' => "Sale for unit {$this->sale->unit->unit_number} has been completed. Total paid: " . number_format($this->sale->total_paid, 2),
            'url' => url('/sales/' . $this->sale->uuid),
            'sale_id' => $this->sale->id,
        ];
    }
}
