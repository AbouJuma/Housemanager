<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TYPES = [
        'tenant_welcome', 'landlord_welcome', 'buyer_welcome', 'lease_payment', 'buyer_bill',
        'buyer_invoice', 'landlord_payment_notification', 'tenant_payment_notification',
        'lease_landlord_notification', 'invoice_due_soon', 'invoice_overdue',
        'installment_due_soon', 'installment_overdue', 'sale_completed',
        'sale_created', 'sale_payment_landlord',
    ];

    private const OLD_BUYER_BILL_SMS = 'Habari {buyer_name}, invoice mpya ya Tsh {amount} (installment #{installment_number}) kwa ajili ya Unit {unit_number} katika {property_name} imetolewa, inatakiwa kulipwa tarehe {due_date}. Balance yako ni Tsh {balance}. Asante.';

    /**
     * Tenant Welcome (sent when a tenant record is created) and the
     * message sent when a lease is created used to share one template,
     * so lease details (unit, dates, rent) could not be part of it. The
     * lease message gets its own `lease_created_tenant` template. Buyer
     * Bill loses its {installment_number} (a standalone invoice has no
     * installment) - replaced only if it is still the original text.
     */
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->enum('type', [...self::TYPES, 'lease_created_tenant'])->change();
        });

        $exists = DB::table('message_templates')->whereNull('company_id')->where('type', 'lease_created_tenant')->exists();

        if (! $exists) {
            DB::table('message_templates')->insert([
                'company_id' => null,
                'type' => 'lease_created_tenant',
                'name' => 'Lease Created (Tenant)',
                'sms_template' => 'Karibu {tenant_name}. Mkataba wako wa Unit {unit_number} katika {property_name} unaanza {start_date} hadi {end_date}. Kodi ni Tsh {rent_amount}. Lipia kupitia control number {control_number}, bank {bank_name} a/c {account_number}. Asante.',
                'email_subject' => 'Your lease - Unit {unit_number}, {property_name}',
                'email_template' => "Hi {tenant_name},\n\nYour lease for Unit {unit_number} at {property_name} runs from {start_date} to {end_date}.\n\nRent: Tsh {rent_amount}\nDeposit: Tsh {deposit_amount}\n\nPay via control number {control_number}, bank {bank_name}, account {account_number}.\n\nThank you.",
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('message_templates')
            ->whereNull('company_id')
            ->where('type', 'buyer_bill')
            ->where('sms_template', self::OLD_BUYER_BILL_SMS)
            ->update([
                'sms_template' => 'Habari {buyer_name}, invoice mpya {invoice_number} ya Tsh {amount} kwa ajili ya Unit {unit_number} katika {property_name} imetolewa, inatakiwa kulipwa tarehe {due_date}. Balance yako ni Tsh {balance}. Asante.',
                'email_subject' => 'New Invoice {invoice_number} - Unit {unit_number}, due {due_date}',
                'email_template' => 'A new invoice {invoice_number} of Tsh {amount} for Unit {unit_number} at {property_name} is due on {due_date}. Balance: Tsh {balance}.',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('message_templates')->where('type', 'lease_created_tenant')->delete();

        Schema::table('message_templates', function (Blueprint $table) {
            $table->enum('type', self::TYPES)->change();
        });
    }
};
