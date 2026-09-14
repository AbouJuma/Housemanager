<?php

namespace App\Modules\Sales\Notifications;

use App\Modules\Sales\Models\Installment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentOverdue extends Notification
{
    use Queueable;

    public function __construct(protected Installment $installment)
    {
    }

    public function via($notifiable): array
    {
        return ['database', 'mail', 'sms'];
    }

    public function toMail($notifiable)
    {
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->subject('Payment Overdue - Installment #' . $this->installment->installment_number)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('Your installment payment is now overdue.')
            ->line('Installment #' . $this->installment->installment_number . ' of ' . $this->installment->sale->installments->count())
            ->line('Amount: ' . number_format($this->installment->amount, 2))
            ->line('Due Date: ' . $this->installment->due_date->format('M j, Y'))
            ->line('Please make your payment as soon as possible to avoid any issues.')
            ->action('View Sale', url('/sales/' . $this->installment->sale->uuid))
            ->line('Thank you.');
    }

    public function toSms($notifiable)
    {
        return "Payment overdue: Installment #{$this->installment->installment_number} of " . number_format($this->installment->amount, 2) . " was due on " . $this->installment->due_date->format('M j, Y') . ". Please pay immediately.";
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Payment Overdue',
            'message' => "Installment #{$this->installment->installment_number} payment of " . number_format($this->installment->amount, 2) . " is overdue since " . $this->installment->due_date->format('M j, Y'),
            'url' => url('/sales/' . $this->installment->sale->uuid),
            'installment_id' => $this->installment->id,
            'sale_id' => $this->installment->sale->id,
        ];
    }
}
