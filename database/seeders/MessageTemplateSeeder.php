<?php

namespace Database\Seeders;

use App\Models\MessageTemplate;
use App\Modules\Companies\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MessageTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create shared templates for all companies (company_id = null)
        $this->createSharedTemplates();
    }

    protected function createSharedTemplates(): void
    {
        $templates = [
            [
                'type' => 'tenant_welcome',
                'name' => 'Tenant Welcome Message',
                'sms_template' => 'karibu {tenant_name} kwenye Apartment {company_name} login kwa www.apartments.ecofieldgroup.com phone:{phone} utapata otp
Lipia kodi yako kupitia control namba yako ni {control_number}',
                'email_subject' => 'Welcome to {company_name}',
                'email_template' => 'Hi {tenant_name},

Welcome to {company_name}. Your control number is {control_number}. Sign in with your phone number {phone}.',
            ],
            [
                'type' => 'landlord_welcome',
                'name' => 'Landlord Welcome Message',
                'sms_template' => 'karibu {landlord_name} kwenye {company_name}. Umefanikiwa kujiunga na mfumo wetu kama mmiliki wa mali. Mali zako: {property_names}. Karibu!',
                'email_subject' => 'Welcome to {company_name} - Landlord Registration',
                'email_template' => 'Welcome to {company_name}',
            ],
            [
                'type' => 'buyer_welcome',
                'name' => 'Buyer Welcome Message',
                'sms_template' => 'karibu {buyer_name} kwenye {company_name}. Control number yako ni {control_number}. Tutakutumia maelezo ya ununuzi wako.',
                'email_subject' => 'Welcome to {company_name}',
                'email_template' => 'Hi {buyer_name},

Welcome to {company_name}. Your control number is {control_number}.',
            ],
            [
                'type' => 'lease_payment',
                'name' => 'Lease Invoice Notice',
                'sms_template' => 'Habari {tenant_name}, invoice mpya ya Tsh {amount} kwa ajili ya Unit {unit_number} katika {property_name} imetolewa, inatakiwa kulipwa tarehe {due_date}. Balance yako ni Tsh {balance}. Asante.',
                'email_subject' => 'New Invoice - Unit {unit_number}, due {due_date}',
                'email_template' => 'A new invoice of Tsh {amount} for Unit {unit_number} at {property_name} is due on {due_date}. Balance: Tsh {balance}.',
            ],
            [
                'type' => 'buyer_bill',
                'name' => 'Buyer Invoice Notice',
                'sms_template' => 'Habari {buyer_name}, invoice mpya {invoice_number} ya Tsh {amount} kwa ajili ya Unit {unit_number} katika {property_name} imetolewa, inatakiwa kulipwa tarehe {due_date}. Balance yako ni Tsh {balance}. Asante.',
                'email_subject' => 'New Invoice {invoice_number} - Unit {unit_number}, due {due_date}',
                'email_template' => 'A new invoice {invoice_number} of Tsh {amount} for Unit {unit_number} at {property_name} is due on {due_date}. Balance: Tsh {balance}.',
            ],
            [
                'type' => 'buyer_invoice',
                'name' => 'Buyer Invoice Payment Confirmation',
                'sms_template' => 'Tumepokea malipo yako ya Tsh {amount} ya awamu ya {installment_number} kati ya {installments_total} (Unit {unit_number}, {property_name}) tarehe {payment_date}. Kilichobaki kwenye awamu hii: Tsh {installment_balance}. Jumla iliyobaki: Tsh {balance}, awamu {installments_remaining} zimebaki. Asante.',
                'email_subject' => 'Payment received - Installment {installment_number} of {installments_total}',
                'email_template' => 'Hi {buyer_name},

We received your payment of Tsh {amount} on {payment_date} for installment {installment_number} of {installments_total} (Unit {unit_number} at {property_name}).

Remaining on this installment: Tsh {installment_balance}
Total remaining: Tsh {balance}
Installments remaining: {installments_remaining}

Thank you.',
            ],
            [
                'type' => 'landlord_payment_notification',
                'name' => 'Landlord Payment Notification',
                'sms_template' => 'Umepokea malipo ya Tsh {amount} kutoka kwa {tenant_name} kwa ajili ya Unit {unit_number} katika {property_name}. Tarehe ya malipo: {payment_date}. Asante.',
                'email_subject' => 'Payment Received from {tenant_name}',
                'email_template' => 'Payment notification',
            ],
            [
                'type' => 'tenant_payment_notification',
                'name' => 'Tenant Payment Notification',
                'sms_template' => 'Umefanikiwa kulipa Tsh {amount} kwa ajili ya yako ya Unit {unit_number} katika {property_name}. Tarehe ya malipo: {payment_date}. Balance yako ni Tsh {balance}. Asante.',
                'email_subject' => 'Payment Confirmation - {payment_date}',
                'email_template' => 'Payment confirmation',
            ],
            [
                'type' => 'lease_landlord_notification',
                'name' => 'Lease Landlord Notification',
                'sms_template' => 'Habari {landlord_name}, Unit {unit_number} katika {property_name} imeshikwa na {tenant_name} (Simu: {tenant_phone}). Mkataba unaanza {start_date} hadi {end_date}. Kodi ya mwezi ni Tsh {rent_amount}.',
                'email_subject' => 'Unit Rented - {property_name}',
                'email_template' => 'Unit rental notification',
            ],
            [
                'type' => 'invoice_due_soon',
                'name' => 'Rent Invoice Due Soon',
                'sms_template' => 'Kumbusho: Invoice {invoice_number} ya Tsh {amount} kwa ajili ya Unit {unit_number} katika {property_name} inatakiwa kulipwa tarehe {due_date}.',
                'email_subject' => 'Rent due {due_date} - Invoice {invoice_number}',
                'email_template' => 'Hi {tenant_name},

A reminder that invoice {invoice_number} for Tsh {amount} at {property_name}, Unit {unit_number} is due on {due_date}.

Please arrange payment before then.',
            ],
            [
                'type' => 'invoice_overdue',
                'name' => 'Rent Invoice Overdue',
                'sms_template' => 'Invoice {invoice_number} ya Tsh {amount} kwa ajili ya Unit {unit_number} katika {property_name} ilitakiwa kulipwa tarehe {due_date} na bado haijalipwa.',
                'email_subject' => 'Overdue - Invoice {invoice_number}',
                'email_template' => 'Hi {tenant_name},

Invoice {invoice_number} for Tsh {amount} at {property_name}, Unit {unit_number} was due on {due_date} and remains unpaid.

Please arrange payment as soon as possible.',
            ],
            [
                'type' => 'installment_due_soon',
                'name' => 'Installment Due Soon',
                'sms_template' => 'Kumbusho: Installment #{installment_number} ya Tsh {amount} kwa ajili ya Unit {unit_number} katika {property_name} inatakiwa kulipwa tarehe {due_date}.',
                'email_subject' => 'Payment due {due_date} - Installment #{installment_number}',
                'email_template' => 'Hi {buyer_name},

A reminder that installment #{installment_number} for Tsh {amount} at {property_name}, Unit {unit_number} is due on {due_date}.

Please arrange payment before then.',
            ],
            [
                'type' => 'installment_overdue',
                'name' => 'Installment Overdue',
                'sms_template' => 'Installment #{installment_number} ya Tsh {amount} kwa ajili ya Unit {unit_number} katika {property_name} ilitakiwa kulipwa tarehe {due_date} na bado haijalipwa.',
                'email_subject' => 'Overdue - Installment #{installment_number}',
                'email_template' => 'Hi {buyer_name},

Installment #{installment_number} for Tsh {amount} at {property_name}, Unit {unit_number} was due on {due_date} and remains unpaid.

Please arrange payment as soon as possible.',
            ],
            [
                'type' => 'sale_completed',
                'name' => 'Sale Completed',
                'sms_template' => 'Malipo yote ya Unit {unit_number} katika {property_name} yamekamilika (mnunuzi: {buyer_name}). Jumla iliyolipwa: Tsh {total_paid}. Asante!',
                'email_subject' => 'Sale completed - {unit_number}',
                'email_template' => 'The sale for Unit {unit_number} at {property_name} has been completed - all installments have been paid (buyer: {buyer_name}). Total price: Tsh {total_price}. Total paid: Tsh {total_paid}. Thank you for your business!',
            ],
            [
                'type' => 'sale_created',
                'name' => 'Sale Created (Buyer)',
                'sms_template' => 'Habari {buyer_name}, umenunua Unit {unit_number} katika {property_name}. Bei ya jumla: Tsh {total_price}. Umelipa: Tsh {total_paid}. Kilichobaki: Tsh {balance}. Malipo ni awamu {installments_total} za Tsh {installment_amount}, kuanzia {first_due_date} hadi {last_due_date}. Asante.',
                'email_subject' => 'Purchase details - Unit {unit_number}, {property_name}',
                'email_template' => 'Hi {buyer_name},

Thank you for your purchase of Unit {unit_number} at {property_name}.

Total price: Tsh {total_price}
Paid so far: Tsh {total_paid}
Balance: Tsh {balance}

Payment plan: {installments_total} installments of Tsh {installment_amount}, from {first_due_date} to {last_due_date}.',
            ],
            [
                'type' => 'sale_payment_landlord',
                'name' => 'Sale Payment (Landlord)',
                'sms_template' => 'Umepokea malipo ya Tsh {amount} kutoka kwa {buyer_name} - awamu ya {installment_number} kati ya {installments_total}, Unit {unit_number} katika {property_name} ({payment_date}). Iliyobaki kulipwa: Tsh {balance}, awamu {installments_remaining} zimebaki. Asante.',
                'email_subject' => 'Payment received from {buyer_name} - Unit {unit_number}',
                'email_template' => 'Payment of Tsh {amount} received from {buyer_name} on {payment_date} for installment {installment_number} of {installments_total} (Unit {unit_number} at {property_name}).

Total remaining: Tsh {balance}
Installments remaining: {installments_remaining}',
            ],
            [
                'type' => 'lease_created_tenant',
                'name' => 'Lease Created (Tenant)',
                'sms_template' => 'Karibu {tenant_name}. Mkataba wako wa Unit {unit_number} katika {property_name} unaanza {start_date} hadi {end_date}. Kodi ni Tsh {rent_amount}. Lipia kupitia control number {control_number}, bank {bank_name}, jina la akaunti {account_name}, a/c {account_number}. Asante.',
                'email_subject' => 'Your lease - Unit {unit_number}, {property_name}',
                'email_template' => 'Hi {tenant_name},

Your lease for Unit {unit_number} at {property_name} runs from {start_date} to {end_date}.

Rent: Tsh {rent_amount}
Deposit: Tsh {deposit_amount}

Pay via control number {control_number}, bank {bank_name}, account name {account_name}, account number {account_number}.

Thank you.',
            ],
        ];

        // Welcome messages carry the new account's login details - use the
        // canonical wording (MessageTemplate::getDefaultTemplate) for them.
        $templates[] = ['type' => 'staff_welcome', 'name' => 'Staff Welcome'];
        $templates = array_map(
            fn (array $template) => in_array($template['type'], ['tenant_welcome', 'landlord_welcome', 'buyer_welcome', 'staff_welcome'], true)
                ? [...$template, ...MessageTemplate::getDefaultTemplate($template['type'])]
                : $template,
            $templates,
        );

        foreach ($templates as $template) {
            MessageTemplate::firstOrCreate(
                [
                    'company_id' => null,
                    'type' => $template['type'],
                ],
                [
                    'name' => $template['name'],
                    'sms_template' => $template['sms_template'],
                    'email_subject' => $template['email_subject'],
                    'email_template' => $template['email_template'],
                    'is_active' => true,
                ]
            );
        }
    }
}
