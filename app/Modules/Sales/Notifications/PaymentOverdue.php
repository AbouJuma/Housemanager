<?php

namespace App\Modules\Sales\Notifications;

use App\Modules\PaymentReminders\Models\PaymentReminderRule;
use App\Modules\Sales\Models\Installment;
use App\Notifications\TemplatedNotification;

/**
 * Sent once a buyer's installment is marked overdue, per a company's
 * own configured PaymentReminderRule - see
 * App\Modules\Sales\Console\Commands\SendPaymentReminders. Text comes
 * from the `installment_overdue` MessageTemplate - see PaymentDueSoon's
 * docblock for why this extends TemplatedNotification.
 */
class PaymentOverdue extends TemplatedNotification
{
    public function __construct(protected Installment $installment, protected ?PaymentReminderRule $rule = null)
    {
        $sale = $installment->sale;
        $unit = $sale->unit;
        $property = $unit->building->property;

        parent::__construct('installment_overdue', [
            'buyer_name' => $sale->buyer->name,
            'installment_number' => $installment->installment_number,
            'amount' => number_format($installment->amount, 0),
            'due_date' => $installment->due_date->format('M j, Y'),
            'unit_number' => $unit->unit_number,
            'property_name' => $property->name,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->summary($notifiable),
            'url' => url('/sales/'.$this->installment->sale->uuid),
            'installment_id' => $this->installment->id,
            'sale_id' => $this->installment->sale->id,
            'rule_id' => $this->rule?->id,
        ];
    }

    protected function title(): string
    {
        return 'Payment overdue';
    }

    protected function summary(mixed $notifiable): string
    {
        return "Installment #{$this->installment->installment_number} payment of ".number_format($this->installment->amount, 2).' is overdue since '.$this->installment->due_date->format('M j, Y');
    }
}
