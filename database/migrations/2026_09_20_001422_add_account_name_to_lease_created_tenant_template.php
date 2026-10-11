<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const OLD_SMS = 'Karibu {tenant_name}. Mkataba wako wa Unit {unit_number} katika {property_name} unaanza {start_date} hadi {end_date}. Kodi ni Tsh {rent_amount}. Lipia kupitia control number {control_number}, bank {bank_name} a/c {account_number}. Asante.';

    private const OLD_EMAIL = "Hi {tenant_name},\n\nYour lease for Unit {unit_number} at {property_name} runs from {start_date} to {end_date}.\n\nRent: Tsh {rent_amount}\nDeposit: Tsh {deposit_amount}\n\nPay via control number {control_number}, bank {bank_name}, account {account_number}.\n\nThank you.";

    /**
     * A bank account is a bank name, an account name AND a number - the
     * lease-created message only had the first and last. Each field is
     * replaced only if it is still the original default, so wording an
     * admin already changed is never overwritten.
     */
    public function up(): void
    {
        $base = DB::table('message_templates')->whereNull('company_id')->where('type', 'lease_created_tenant');

        (clone $base)->where('sms_template', self::OLD_SMS)->update([
            'sms_template' => 'Karibu {tenant_name}. Mkataba wako wa Unit {unit_number} katika {property_name} unaanza {start_date} hadi {end_date}. Kodi ni Tsh {rent_amount}. Lipia kupitia control number {control_number}, bank {bank_name}, jina la akaunti {account_name}, a/c {account_number}. Asante.',
            'updated_at' => now(),
        ]);

        (clone $base)->where('email_template', self::OLD_EMAIL)->update([
            'email_template' => "Hi {tenant_name},\n\nYour lease for Unit {unit_number} at {property_name} runs from {start_date} to {end_date}.\n\nRent: Tsh {rent_amount}\nDeposit: Tsh {deposit_amount}\n\nPay via control number {control_number}, bank {bank_name}, account name {account_name}, account number {account_number}.\n\nThank you.",
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Wording change only - nothing to undo.
    }
};
