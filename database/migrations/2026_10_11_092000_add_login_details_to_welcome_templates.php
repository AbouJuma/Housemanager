<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every new staff user, tenant, landlord and buyer now gets portal/app
 * access straight away (App\Core\Auth\AccountProvisioner), so their
 * welcome message carries {username}, {password} and {login_url}.
 * Adds the `staff_welcome` type, and appends the login lines to existing
 * welcome templates that don't mention them yet - edited wording is kept.
 */
return new class extends Migration
{
    private const TYPES = [
        'tenant_welcome', 'landlord_welcome', 'buyer_welcome', 'lease_payment', 'buyer_bill',
        'buyer_invoice', 'landlord_payment_notification', 'tenant_payment_notification',
        'lease_landlord_notification', 'invoice_due_soon', 'invoice_overdue',
        'installment_due_soon', 'installment_overdue', 'sale_completed',
        'sale_created', 'sale_payment_landlord', 'lease_created_tenant',
    ];

    private const SMS_LOGIN = ' Ingia: {login_url} Username: {username} Password: {password}';

    private const EMAIL_LOGIN = "\n\nSign in at {login_url}\nUsername: {username}\nPassword: {password}\n\nYou can also sign in with your phone number. Please change your password after your first sign-in.";

    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->enum('type', [...self::TYPES, 'staff_welcome'])->change();
        });

        // The old tenant SMS promised an OTP login that never existed.
        DB::table('message_templates')
            ->where('type', 'tenant_welcome')
            // Matched on the phrase, not the exact text - saved copies differ in line endings.
            ->where('sms_template', 'like', '%utapata otp%')
            ->update([
                'sms_template' => 'Karibu {tenant_name} kwenye {company_name}. Lipia kodi kupitia control namba {control_number}.'.self::SMS_LOGIN,
                'updated_at' => now(),
            ]);

        DB::table('message_templates')
            ->whereIn('type', ['tenant_welcome', 'landlord_welcome', 'buyer_welcome'])
            ->get(['id', 'sms_template', 'email_template'])
            ->each(function ($row): void {
                $changes = [];

                if (! str_contains((string) $row->sms_template, '{username}')) {
                    $changes['sms_template'] = rtrim((string) $row->sms_template).self::SMS_LOGIN;
                }
                if (! str_contains((string) $row->email_template, '{username}')) {
                    $changes['email_template'] = rtrim((string) $row->email_template).self::EMAIL_LOGIN;
                }

                if ($changes) {
                    DB::table('message_templates')->where('id', $row->id)->update($changes + ['updated_at' => now()]);
                }
            });

        if (! DB::table('message_templates')->whereNull('company_id')->where('type', 'staff_welcome')->exists()) {
            DB::table('message_templates')->insert([
                'company_id' => null,
                'type' => 'staff_welcome',
                'name' => 'Staff Welcome',
                'sms_template' => 'Karibu {staff_name} kwenye {company_name}.'.self::SMS_LOGIN,
                'email_subject' => 'Your {company_name} account',
                'email_template' => "Hi {staff_name},\n\nAn account has been created for you at {company_name}.".self::EMAIL_LOGIN,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('message_templates')->where('type', 'staff_welcome')->delete();

        Schema::table('message_templates', function (Blueprint $table) {
            $table->enum('type', self::TYPES)->change();
        });
    }
};
