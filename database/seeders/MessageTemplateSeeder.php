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
Lipia kodi yako kupitia control namba yako ni {control_number} bank {bank_name} a/c no.{account_number} kwaajili ya malipo kiasi cha kulipia ni Tsh {rent_amount}
please pay your rent via Control number {control_number} Bank {bank_name}',
                'email_subject' => 'Welcome to {company_name}',
                'email_template' => 'Welcome to {company_name}',
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
                'sms_template' => 'karibu {buyer_name} kwenye {company_name}. Umefanikiwa kununua Unit {unit_number} katika {property_name}. Lipia kiasi cha Tsh {amount} kwa kutumia control number {control_number} bank {bank_name} a/c {account_number}',
                'email_subject' => 'Welcome to {company_name} - Purchase Confirmation',
                'email_template' => 'Thank you for your purchase',
            ],
            [
                'type' => 'lease_payment',
                'name' => 'Lease Payment Confirmation',
                'sms_template' => 'Imepokea malipo ya Tsh {amount} kwa ajili ya kodi yako ya Unit {unit_number} katika {property_name}. Lease yako ni kwa muda wa {lease_duration}. Balance yako ni Tsh {balance}. Asante.',
                'email_subject' => 'Payment Received - Lease {lease_number}',
                'email_template' => 'Payment confirmation',
            ],
            [
                'type' => 'buyer_invoice',
                'name' => 'Buyer Invoice Payment Confirmation',
                'sms_template' => 'Imepokea malipo ya Tsh {amount} kwa ajili ya invoice yako ya Unit {unit_number} katika {property_name}. Payment terms: {payment_terms}. Balance yako ni Tsh {balance}. Asante.',
                'email_subject' => 'Payment Received - Invoice {invoice_number}',
                'email_template' => 'Payment confirmation',
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
        ];

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
