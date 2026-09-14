<?php

namespace App\Models;

use App\Core\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;

class MessageTemplate extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'sms_template',
        'email_subject',
        'email_template',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the default template for a type
     */
    public static function getDefaultTemplate(string $type): array
    {
        return match($type) {
            'tenant_welcome' => [
                'sms_template' => 'karibu {tenant_name} kwenye Apartment {company_name} login kwa www.apartments.ecofieldgroup.com phone:{phone} utapata otp
Lipia kodi yako kupitia control namba yako ni {control_number} bank {bank_name} a/c no.{account_number} kwaajili ya malipo kiasi cha kulipia ni Tsh {rent_amount}
please pay your rent via Control number {control_number} Bank {bank_name}',
                'email_subject' => 'Welcome to {company_name}',
                'email_template' => 'Welcome to {company_name}',
            ],
            'landlord_welcome' => [
                'sms_template' => 'karibu {landlord_name} kwenye {company_name}. Umefanikiwa kujiunga na mfumo wetu kama mmiliki wa mali. Mali zako: {property_names}. Karibu!',
                'email_subject' => 'Welcome to {company_name} - Landlord Registration',
                'email_template' => 'Welcome to {company_name}',
            ],
            'lease_landlord_notification' => [
                'sms_template' => 'Habari {landlord_name}, Unit {unit_number} katika {property_name} imeshikwa na {tenant_name} (Simu: {tenant_phone}). Mkataba unaanza {start_date} hadi {end_date}. Kodi ya mwezi ni Tsh {rent_amount}.',
                'email_subject' => 'Unit Rented - {property_name}',
                'email_template' => 'Unit rental notification',
            ],
            'buyer_welcome' => [
                'sms_template' => 'karibu {buyer_name} kwenye {company_name}. Umefanikiwa kununua Unit {unit_number} katika {property_name}. Lipia kiasi cha Tsh {amount} kwa kutumia control number {control_number} bank {bank_name} a/c {account_number}',
                'email_subject' => 'Welcome to {company_name} - Purchase Confirmation',
                'email_template' => 'Thank you for your purchase',
            ],
            'lease_payment' => [
                'sms_template' => 'Imepokea malipo ya Tsh {amount} kwa ajili ya kodi yako ya Unit {unit_number} katika {property_name}. Lease yako ni kwa muda wa {lease_duration}. Balance yako ni Tsh {balance}. Asante.',
                'email_subject' => 'Payment Received - Lease {lease_number}',
                'email_template' => 'Payment confirmation',
            ],
            'buyer_invoice' => [
                'sms_template' => 'Imepokea malipo ya Tsh {amount} kwa ajili ya invoice yako ya Unit {unit_number} katika {property_name}. Payment terms: {payment_terms}. Balance yako ni Tsh {balance}. Asante.' ,
                'email_subject' => 'Payment Received - Invoice {invoice_number}',
                'email_template' => 'Payment confirmation',
            ],
            'landlord_payment_notification' => [
                'sms_template' => 'Umepokea malipo ya Tsh {amount} kutoka kwa {tenant_name} kwa ajili ya Unit {unit_number} katika {property_name}. Tarehe ya malipo: {payment_date}. Asante.',
                'email_subject' => 'Payment Received from {tenant_name}',
                'email_template' => 'Payment notification',
            ],
            'tenant_payment_notification' => [
                'sms_template' => 'Umefanikiwa kulipa Tsh {amount} kwa ajili ya yako ya Unit {unit_number} katika {property_name}. Tarehe ya malipo: {payment_date}. Balance yako ni Tsh {balance}. Asante.',
                'email_subject' => 'Payment Confirmation - {payment_date}',
                'email_template' => 'Payment confirmation',
            ],
            default => [
                'sms_template' => '',
                'email_subject' => '',
                'email_template' => '',
            ],
        };
    }

    /**
     * Replace placeholders with actual data
     */
    public function renderSmsTemplate(array $data): string
    {
        $template = $this->sms_template ?? self::getDefaultTemplate($this->type)['sms_template'];
        return $this->replacePlaceholders($template, $data);
    }

    /**
     * Replace placeholders in email subject
     */
    public function renderEmailSubject(array $data): string
    {
        $template = $this->email_subject ?? self::getDefaultTemplate($this->type)['email_subject'];
        return $this->replacePlaceholders($template, $data);
    }

    /**
     * Replace placeholders in email template
     */
    public function renderEmailTemplate(array $data): string
    {
        $template = $this->email_template ?? self::getDefaultTemplate($this->type)['email_template'];
        return $this->replacePlaceholders($template, $data);
    }

    /**
     * Replace placeholders with data
     */
    protected function replacePlaceholders(string $template, array $data): string
    {
        foreach ($data as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }
}
