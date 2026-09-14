<?php

namespace App\Modules\Sales\Console\Commands;

use App\Modules\Sales\Models\Installment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Notifications\PaymentDueSoon;
use App\Modules\Sales\Notifications\PaymentOverdue;
use App\Modules\Sales\Notifications\SaleCompleted;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendPaymentReminders extends Command
{
    protected $signature = 'sales:send-payment-reminders';
    protected $description = 'Send payment reminders for upcoming and overdue installments';

    public function handle(): int
    {
        $this->info('Checking for payment reminders...');

        // Send reminders for installments due within 7 days
        $dueSoonInstallments = Installment::where('status', 'pending')
            ->where('due_date', '<=', now()->addDays(7))
            ->where('due_date', '>=', now())
            ->with('sale.buyer')
            ->get();

        $dueSoonCount = 0;
        foreach ($dueSoonInstallments as $installment) {
            if ($installment->sale->buyer) {
                // Check if already notified in the last 7 days
                if (!$this->alreadyNotified($installment->sale->buyer, PaymentDueSoon::class, $installment->id, 7)) {
                    $installment->sale->buyer->notify(new PaymentDueSoon($installment));
                    $dueSoonCount++;
                    $this->info("Sent due-soon reminder for installment #{$installment->installment_number} to {$installment->sale->buyer->name}");
                }
            }
        }

        // Send overdue notifications
        $overdueInstallments = Installment::where('status', 'pending')
            ->where('due_date', '<', now())
            ->with('sale.buyer')
            ->get();

        $overdueCount = 0;
        foreach ($overdueInstallments as $installment) {
            if ($installment->sale->buyer) {
                // Check if already notified in the last 30 days
                if (!$this->alreadyNotified($installment->sale->buyer, PaymentOverdue::class, $installment->id, 30)) {
                    $installment->markAsOverdue();
                    $installment->sale->buyer->notify(new PaymentOverdue($installment));
                    $overdueCount++;
                    $this->info("Sent overdue notification for installment #{$installment->installment_number} to {$installment->sale->buyer->name}");
                }
            }
        }

        // Check for completed sales and notify
        $completedSales = Sale::where('status', 'active')
            ->whereHas('installments', function ($query) {
                $query->where('status', '!=', 'paid');
            })
            ->get()
            ->filter(function ($sale) {
                return $sale->remaining_balance <= 0;
            });

        $completedCount = 0;
        foreach ($completedSales as $sale) {
            if ($sale->buyer) {
                if (!$this->alreadyNotified($sale->buyer, SaleCompleted::class, $sale->id)) {
                    $sale->markAsCompleted();
                    $sale->buyer->notify(new SaleCompleted($sale));
                    $completedCount++;
                    $this->info("Sent sale completion notification to {$sale->buyer->name}");
                    
                    // Also notify landlord
                    if ($sale->unit->building->property->landlord) {
                        $sale->unit->building->property->landlord->notify(new SaleCompleted($sale));
                        $this->info("Sent sale completion notification to landlord");
                    }
                }
            }
        }

        $this->info("Payment reminders sent: {$dueSoonCount} due-soon, {$overdueCount} overdue, {$completedCount} completed.");
        
        return Command::SUCCESS;
    }

    protected function alreadyNotified($notifiable, string $notificationClass, int $recordId, ?int $withinDays = null): bool
    {
        return $notifiable->notifications()
            ->where('type', $notificationClass)
            ->where(function ($query) use ($recordId) {
                $query->where('data->installment_id', $recordId)
                    ->orWhere('data->sale_id', $recordId);
            })
            ->when($withinDays, fn ($query) => $query->where('created_at', '>=', now()->subDays($withinDays)))
            ->exists();
    }
}
