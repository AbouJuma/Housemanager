<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const OLD_BUYER_INVOICE_SMS = 'Imepokea malipo ya Tsh {amount} kwa ajili ya invoice yako ya Unit {unit_number} katika {property_name}. Payment terms: {payment_terms}. Balance yako ni Tsh {balance}. Asante.';

    private const TYPES = [
        'tenant_welcome', 'landlord_welcome', 'buyer_welcome', 'lease_payment', 'buyer_bill',
        'buyer_invoice', 'landlord_payment_notification', 'tenant_payment_notification',
        'lease_landlord_notification', 'invoice_due_soon', 'invoice_overdue',
        'installment_due_soon', 'installment_overdue', 'sale_completed',
    ];

    /**
     * Adds the two sale templates (one message when a sale is created,
     * one to the landlord when an installment is paid) and rewords the
     * buyer's payment template to say which installment was paid and
     * what remains. The buyer_invoice text is only replaced if it is
     * still the original seeded default - an admin's own wording is
     * never overwritten.
     */
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->enum('type', [...self::TYPES, 'sale_created', 'sale_payment_landlord'])->change();
        });

        $now = now();

        foreach ($this->newTemplates() as $type => $template) {
            $exists = DB::table('message_templates')->whereNull('company_id')->where('type', $type)->exists();

            if (! $exists) {
                DB::table('message_templates')->insert([
                    'company_id' => null,
                    'type' => $type,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                    ...$template,
                ]);
            }
        }

        $buyerInvoice = $this->buyerInvoiceTemplate();

        DB::table('message_templates')
            ->whereNull('company_id')
            ->where('type', 'buyer_invoice')
            ->where('sms_template', self::OLD_BUYER_INVOICE_SMS)
            ->update([...$buyerInvoice, 'updated_at' => $now]);
    }

    public function down(): void
    {
        DB::table('message_templates')->whereIn('type', ['sale_created', 'sale_payment_landlord'])->delete();

        Schema::table('message_templates', function (Blueprint $table) {
            $table->enum('type', self::TYPES)->change();
        });
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function newTemplates(): array
    {
        return [
            'sale_created' => [
                'name' => 'Sale Created (Buyer)',
                'sms_template' => 'Habari {buyer_name}, umenunua Unit {unit_number} katika {property_name}. Bei ya jumla: Tsh {total_price}. Umelipa: Tsh {total_paid}. Kilichobaki: Tsh {balance}. Malipo ni awamu {installments_total} za Tsh {installment_amount}, kuanzia {first_due_date} hadi {last_due_date}. Asante.',
                'email_subject' => 'Purchase details - Unit {unit_number}, {property_name}',
                'email_template' => "Hi {buyer_name},\n\nThank you for your purchase of Unit {unit_number} at {property_name}.\n\nTotal price: Tsh {total_price}\nPaid so far: Tsh {total_paid}\nBalance: Tsh {balance}\n\nPayment plan: {installments_total} installments of Tsh {installment_amount}, from {first_due_date} to {last_due_date}.",
            ],
            'sale_payment_landlord' => [
                'name' => 'Sale Payment (Landlord)',
                'sms_template' => 'Umepokea malipo ya Tsh {amount} kutoka kwa {buyer_name} - awamu ya {installment_number} kati ya {installments_total}, Unit {unit_number} katika {property_name} ({payment_date}). Iliyobaki kulipwa: Tsh {balance}, awamu {installments_remaining} zimebaki. Asante.',
                'email_subject' => 'Payment received from {buyer_name} - Unit {unit_number}',
                'email_template' => "Payment of Tsh {amount} received from {buyer_name} on {payment_date} for installment {installment_number} of {installments_total} (Unit {unit_number} at {property_name}).\n\nTotal remaining: Tsh {balance}\nInstallments remaining: {installments_remaining}",
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buyerInvoiceTemplate(): array
    {
        return [
            'sms_template' => 'Tumepokea malipo yako ya Tsh {amount} ya awamu ya {installment_number} kati ya {installments_total} (Unit {unit_number}, {property_name}) tarehe {payment_date}. Kilichobaki kwenye awamu hii: Tsh {installment_balance}. Jumla iliyobaki: Tsh {balance}, awamu {installments_remaining} zimebaki. Asante.',
            'email_subject' => 'Payment received - Installment {installment_number} of {installments_total}',
            'email_template' => "Hi {buyer_name},\n\nWe received your payment of Tsh {amount} on {payment_date} for installment {installment_number} of {installments_total} (Unit {unit_number} at {property_name}).\n\nRemaining on this installment: Tsh {installment_balance}\nTotal remaining: Tsh {balance}\nInstallments remaining: {installments_remaining}\n\nThank you.",
        ];
    }
};
