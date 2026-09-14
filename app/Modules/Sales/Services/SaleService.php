<?php

namespace App\Modules\Sales\Services;

use App\Modules\Invoices\Models\Invoice;
use App\Modules\Invoices\Models\InvoiceItem;
use App\Modules\Sales\Models\Installment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Tenants\Services\BeemsSmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SaleService
{
    protected BeemsSmsService $smsService;

    public function __construct(BeemsSmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function create(array $data): Sale
    {
        return DB::transaction(function () use ($data) {
            $sale = Sale::create($data);

            // Generate installment schedule
            $this->generateInstallmentSchedule($sale);

            // Mark as active if down payment is made
            if ($sale->down_payment > 0) {
                $sale->status = 'active';
                $sale->save();
            }

            // Send welcome message to buyer
            $this->sendBuyerWelcomeMessage($sale);

            return $sale;
        });
    }

    public function update(Sale $sale, array $data): Sale
    {
        return DB::transaction(function () use ($sale, $data) {
            $sale->update($data);

            // If installment period changed, regenerate schedule
            if (isset($data['installment_period']) && $data['installment_period'] != $sale->installment_period) {
                $sale->installments()->delete();
                $this->generateInstallmentSchedule($sale);
            }

            return $sale;
        });
    }

    public function delete(Sale $sale): void
    {
        DB::transaction(function () use ($sale) {
            $sale->installments()->delete();
            $sale->delete();
        });
    }

    public function recordPayment(Installment $installment, float $amount): void
    {
        DB::transaction(function () use ($installment, $amount) {
            $installment->paid_amount += $amount;

            if ($installment->paid_amount >= $installment->amount) {
                $installment->status = 'paid';
                $installment->paid_date = now();
            } else {
                $installment->status = 'partially_paid';
            }

            $installment->save();

            // Create invoice for the payment
            $this->createPaymentInvoice($installment, $amount);

            // Send payment notifications
            $this->sendPaymentNotifications($installment, $amount);

            // Check if sale is completed
            $sale = $installment->sale;
            if ($sale->remaining_balance <= 0) {
                $sale->markAsCompleted();
            }
        });
    }

    /**
     * Send buyer welcome message
     */
    protected function sendBuyerWelcomeMessage(Sale $sale): void
    {
        try {
            $buyer = $sale->buyer;
            $company = $sale->company;
            $unit = $sale->unit;
            $property = $unit->building->property;

            $data = [
                'buyer_name' => $buyer->name,
                'company_name' => $company->name,
                'unit_number' => $unit->unit_number,
                'property_name' => $property->name,
                'amount' => number_format($sale->total_price, 0),
                'control_number' => $sale->control_number ?? 'N/A',
                'bank_name' => 'N/A',
                'account_number' => 'N/A',
            ];

            // Get message template (shared templates for all companies)
            $template = \App\Models\MessageTemplate::whereNull('company_id')
                ->where('type', 'buyer_welcome')
                ->where('is_active', true)
                ->first();

            // If no shared template, try company-specific template
            if (!$template) {
                $template = \App\Models\MessageTemplate::where('company_id', $sale->company_id)
                    ->where('type', 'buyer_welcome')
                    ->where('is_active', true)
                    ->first();
            }

            // Only send messages if template exists and is active
            if (!$template) {
                \Log::warning('No active buyer_welcome template found', [
                    'company_id' => $sale->company_id,
                ]);
                return;
            }

            // Send SMS
            if ($buyer->phone) {
                $smsMessage = $template->renderSmsTemplate($data);
                \Log::info('Sending SMS to buyer', ['phone' => $buyer->phone, 'message' => $smsMessage]);
                $this->smsService->send($buyer->phone, $smsMessage);
            } else {
                \Log::warning('Buyer has no phone number', ['buyer_id' => $buyer->id]);
            }

            // Send Email
            if ($buyer->email) {
                $emailSubject = $template->renderEmailSubject($data);
                $emailBody = $template->renderEmailTemplate($data);
                
                \Log::info('Sending email to buyer', ['email' => $buyer->email]);
                Mail::raw($emailBody, function ($message) use ($buyer, $emailSubject) {
                    $message->to($buyer->email)
                        ->subject($emailSubject);
                });
            } else {
                \Log::warning('Buyer has no email', ['buyer_id' => $buyer->id]);
            }
        } catch (\Exception $e) {
            \Log::error('Error sending buyer welcome message', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Send payment notifications for buyer and landlord
     */
    protected function sendPaymentNotifications(Installment $installment, float $amount): void
    {
        $sale = $installment->sale;
        $buyer = $sale->buyer;
        $unit = $sale->unit;
        $property = $unit->building->property;
        $landlord = $property->landlord;

        // Send notification to buyer
        $this->sendBuyerPaymentNotification($sale, $buyer, $unit, $property, $amount, $installment);

        // Send notification to landlord
        if ($landlord) {
            $this->sendLandlordPaymentNotification($sale, $buyer, $unit, $property, $landlord, $amount, $installment);
        }
    }

    /**
     * Send payment notification to buyer
     */
    protected function sendBuyerPaymentNotification(Sale $sale, $buyer, $unit, $property, float $amount, Installment $installment): void
    {
        try {
            $data = [
                'amount' => number_format($amount, 0),
                'unit_number' => $unit->unit_number,
                'property_name' => $property->name,
                'payment_date' => now()->format('Y-m-d'),
                'balance' => number_format($sale->remaining_balance, 0),
            ];

            // Get message template
            $template = \App\Models\MessageTemplate::whereNull('company_id')
                ->where('type', 'tenant_payment_notification')
                ->where('is_active', true)
                ->first();

            if (!$template || !$buyer->phone) {
                \Log::warning('Cannot send buyer payment notification', [
                    'has_template' => $template !== null,
                    'has_phone' => $buyer->phone !== null
                ]);
                return;
            }

            $smsMessage = $template->renderSmsTemplate($data);
            \Log::info('Sending payment notification to buyer', ['phone' => $buyer->phone, 'message' => $smsMessage]);
            $this->smsService->send($buyer->phone, $smsMessage);
        } catch (\Exception $e) {
            \Log::error('Error sending buyer payment notification', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Send payment notification to landlord
     */
    protected function sendLandlordPaymentNotification(Sale $sale, $buyer, $unit, $property, $landlord, float $amount, Installment $installment): void
    {
        try {
            $data = [
                'amount' => number_format($amount, 0),
                'tenant_name' => $buyer->name,
                'unit_number' => $unit->unit_number,
                'property_name' => $property->name,
                'payment_date' => now()->format('Y-m-d'),
            ];

            // Get message template
            $template = \App\Models\MessageTemplate::whereNull('company_id')
                ->where('type', 'landlord_payment_notification')
                ->where('is_active', true)
                ->first();

            if (!$template || !$landlord->phone) {
                \Log::warning('Cannot send landlord payment notification', [
                    'has_template' => $template !== null,
                    'has_phone' => $landlord->phone !== null
                ]);
                return;
            }

            $smsMessage = $template->renderSmsTemplate($data);
            \Log::info('Sending payment notification to landlord', ['phone' => $landlord->phone, 'message' => $smsMessage]);
            $this->smsService->send($landlord->phone, $smsMessage);
        } catch (\Exception $e) {
            \Log::error('Error sending landlord payment notification', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    protected function createPaymentInvoice(Installment $installment, float $amount): Invoice
    {
        $sale = $installment->sale;

        $invoice = Invoice::create([
            'company_id' => $sale->company_id,
            'sale_id' => $sale->id,
            'invoice_number' => $this->generateInvoiceNumber($sale),
            'issue_date' => now(),
            'due_date' => $installment->due_date,
            'status' => 'sent',
            'total_amount' => $amount,
            'amount_paid' => $amount,
            'notes' => "Payment for installment #{$installment->installment_number}",
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'description' => "Installment #{$installment->installment_number} Payment - Unit {$sale->unit->unit_number}",
            'quantity' => 1,
            'unit_price' => $amount,
            'amount' => $amount,
        ]);

        return $invoice;
    }

    protected function generateInvoiceNumber(Sale $sale): string
    {
        $prefix = 'INV-' . now()->format('Y') . '-';
        $lastInvoice = Invoice::where('company_id', $sale->company_id)
            ->where('invoice_number', 'like', $prefix . '%')
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) str_replace($prefix, '', $lastInvoice->invoice_number);
            $newNumber = str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        } else {
            $newNumber = '00001';
        }

        return $prefix . $newNumber;
    }

    protected function generateInstallmentSchedule(Sale $sale): void
    {
        $remainingAmount = $sale->total_price - $sale->down_payment;
        $installmentAmount = $sale->installment_amount;
        $period = $sale->installment_period;

        // Calculate monthly installment amount if not provided
        if ($installmentAmount == 0 && $period > 0) {
            $installmentAmount = $remainingAmount / $period;
        }

        $startDate = $sale->sale_date->copy()->addMonth();

        for ($i = 1; $i <= $period; $i++) {
            Installment::create([
                'sale_id' => $sale->id,
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'installment_number' => $i,
                'due_date' => $startDate->copy()->addMonths($i - 1),
                'amount' => $installmentAmount,
                'status' => 'pending',
            ]);
        }
    }

    public function paginate(?string $search = null, ?string $status = null, ?int $propertyId = null)
    {
        $query = Sale::with(['unit.building.property', 'buyer']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->whereHas('unit', function ($unitQuery) use ($search) {
                    $unitQuery->where('unit_number', 'like', "%{$search}%");
                })
                ->orWhereHas('buyer', function ($buyerQuery) use ($search) {
                    $buyerQuery->where('name', 'like', "%{$search}%");
                });
            });
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($propertyId) {
            $query->whereHas('unit.building', function ($q) use ($propertyId) {
                $q->where('property_id', $propertyId);
            });
        }

        return $query->latest()->paginate(20);
    }
}
