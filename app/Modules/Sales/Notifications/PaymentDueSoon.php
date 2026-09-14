<?php

namespace App\Modules\Sales\Notifications;

use App\Modules\Sales\Models\Installment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentDueSoon extends Notification
{
    use Queueable;

    public function __construct(protected Installment $installment)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Payment Due Soon - Installment #' . $this->installment->installment_number)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('This is a reminder that your installment payment is due soon.')
            ->line('Installment #' . $this->installment->installment_number . ' of ' . $this->installment->sale->installments->count())
            ->line('Amount: ' . number_format($this->installment->amount, 2))
            ->line('Due Date: ' . $this->installment->due_date->format('M j, Y'))
            ->action('View Sale', url('/sales/' . $this->installment->sale->uuid))
            ->line('Thank you for your continued payments.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Payment Due Soon',
            'message' => "Installment #{$this->installment->installment_number} payment of " . number_format($this->installment->amount, 2) . " is due on " . $this->installment->due_date->format('M j, Y'),
            'url' => url('/sales/' . $this->installment->sale->uuid),
            'installment_id' => $this->installment->id,
            'sale_id' => $this->installment->sale->id,
        ];
    }
}
