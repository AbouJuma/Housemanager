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
     * The ONLY placeholders each template type may use - every one is
     * filled with a real value by the notification that sends it (a
     * test asserts this), and the Message Templates screens build their
     * clickable field buttons from this list. Nothing is listed that the
     * sending code cannot fill.
     *
     * @var array<string, array<int, string>>
     */
    public const PLACEHOLDERS = [
        'tenant_welcome' => ['tenant_name', 'company_name', 'phone', 'control_number', 'username', 'password', 'login_url'],
        'staff_welcome' => ['staff_name', 'company_name', 'username', 'password', 'login_url'],
        'lease_created_tenant' => ['tenant_name', 'company_name', 'property_name', 'unit_number', 'rent_amount', 'deposit_amount', 'start_date', 'end_date', 'control_number', 'bank_name', 'account_name', 'account_number'],
        'landlord_welcome' => ['landlord_name', 'company_name', 'property_names', 'username', 'password', 'login_url'],
        'buyer_welcome' => ['buyer_name', 'company_name', 'phone', 'control_number', 'username', 'password', 'login_url'],
        'lease_landlord_notification' => ['landlord_name', 'property_name', 'unit_number', 'tenant_name', 'tenant_phone', 'start_date', 'end_date', 'rent_amount'],
        'lease_payment' => ['tenant_name', 'invoice_number', 'amount', 'due_date', 'unit_number', 'property_name', 'balance'],
        'buyer_bill' => ['buyer_name', 'invoice_number', 'amount', 'due_date', 'unit_number', 'property_name', 'balance'],
        'tenant_payment_notification' => ['tenant_name', 'invoice_number', 'amount', 'payment_date', 'balance', 'unit_number', 'property_name'],
        'landlord_payment_notification' => ['tenant_name', 'invoice_number', 'amount', 'payment_date', 'balance', 'unit_number', 'property_name'],
        'buyer_invoice' => ['buyer_name', 'unit_number', 'property_name', 'total_price', 'down_payment', 'total_paid', 'balance', 'installments_total', 'installments_paid', 'installments_remaining', 'installment_amount', 'first_due_date', 'last_due_date', 'invoice_number', 'amount', 'payment_date', 'installment_number', 'installment_balance'],
        'sale_payment_landlord' => ['buyer_name', 'unit_number', 'property_name', 'total_price', 'down_payment', 'total_paid', 'balance', 'installments_total', 'installments_paid', 'installments_remaining', 'installment_amount', 'first_due_date', 'last_due_date', 'invoice_number', 'amount', 'payment_date', 'installment_number', 'installment_balance'],
        'sale_created' => ['buyer_name', 'unit_number', 'property_name', 'total_price', 'down_payment', 'total_paid', 'balance', 'installments_total', 'installments_paid', 'installments_remaining', 'installment_amount', 'first_due_date', 'last_due_date'],
        'sale_completed' => ['buyer_name', 'unit_number', 'property_name', 'total_price', 'down_payment', 'total_paid', 'balance', 'installments_total', 'installments_paid', 'installments_remaining', 'installment_amount', 'first_due_date', 'last_due_date'],
        'invoice_due_soon' => ['tenant_name', 'invoice_number', 'amount', 'due_date', 'unit_number', 'property_name'],
        'invoice_overdue' => ['tenant_name', 'invoice_number', 'amount', 'due_date', 'unit_number', 'property_name'],
        'installment_due_soon' => ['buyer_name', 'installment_number', 'amount', 'due_date', 'unit_number', 'property_name'],
        'installment_overdue' => ['buyer_name', 'installment_number', 'amount', 'due_date', 'unit_number', 'property_name'],
    ];

    /**
     * @return array<int, string>
     */
    public static function placeholders(string $type): array
    {
        return self::PLACEHOLDERS[$type] ?? [];
    }

    /**
     * Where each template type is actually used - shown on the Message
     * Templates screens so an admin can tell which template controls
     * which real message. Keep in step with the `parent::__construct(
     * '<type>', ...)` call in each TemplatedNotification subclass.
     *
     * @return array{module: string, recipient: string, when: string}|null
     */
    public static function usage(string $type): ?array
    {
        return [
            'tenant_welcome' => ['module' => 'Tenants', 'recipient' => 'Tenant', 'when' => 'A new tenant is created (before any lease exists - so no rent/unit/bank details). Includes their portal/app login.'],
            'staff_welcome' => ['module' => 'Users', 'recipient' => 'Staff member', 'when' => 'A new staff account is created - their username, starting password and where to sign in.'],
            'lease_created_tenant' => ['module' => 'Leases', 'recipient' => 'Tenant', 'when' => 'A new lease is created for the tenant - unit, dates, rent and payment details.'],
            'landlord_welcome' => ['module' => 'Landlords', 'recipient' => 'Landlord', 'when' => 'A new landlord is created.'],
            'buyer_welcome' => ['module' => 'Sales', 'recipient' => 'Buyer', 'when' => 'A new buyer is created.'],
            'lease_landlord_notification' => ['module' => 'Leases', 'recipient' => 'Landlord', 'when' => 'A new lease is created on one of the landlord\'s units (unit rented / tenant details).'],
            'lease_payment' => ['module' => 'Invoices (rent)', 'recipient' => 'Tenant', 'when' => 'A rent invoice is issued to a tenant (new lease, recurring rent invoice, or a manually sent invoice).'],
            'buyer_bill' => ['module' => 'Invoices (sales)', 'recipient' => 'Buyer', 'when' => 'A standalone invoice (not an installment) is issued to a buyer. Installments are announced by the Sale Created message instead.'],
            'tenant_payment_notification' => ['module' => 'Invoices (rent)', 'recipient' => 'Tenant', 'when' => 'A payment is recorded on a rent invoice - including the deposit entered on a new lease.'],
            'landlord_payment_notification' => ['module' => 'Invoices (rent + sales)', 'recipient' => 'Landlord', 'when' => 'A payment is recorded on a rent or sale invoice for one of their units - including a lease deposit.'],
            'buyer_invoice' => ['module' => 'Sales (installment payment)', 'recipient' => 'Buyer', 'when' => 'An installment payment is recorded - says which installment, how many remain and what is left to pay.'],
            'sale_created' => ['module' => 'Sales', 'recipient' => 'Buyer', 'when' => 'A sale is created - one message with price, amount paid, balance and the installment plan.'],
            'sale_payment_landlord' => ['module' => 'Sales (installment payment)', 'recipient' => 'Landlord', 'when' => 'A buyer pays an installment on one of the landlord\'s units - which installment, how many remain and what is left.'],
            'invoice_due_soon' => ['module' => 'Rent reminders', 'recipient' => 'Tenant', 'when' => 'Daily reminder run, before a rent invoice\'s due date (per the company\'s reminder rules).'],
            'invoice_overdue' => ['module' => 'Rent reminders', 'recipient' => 'Tenant', 'when' => 'Daily reminder run, once a rent invoice is overdue (per the company\'s reminder rules).'],
            'installment_due_soon' => ['module' => 'Sales reminders', 'recipient' => 'Buyer', 'when' => 'Daily reminder run, before a sale installment is due.'],
            'installment_overdue' => ['module' => 'Sales reminders', 'recipient' => 'Buyer', 'when' => 'Daily reminder run, once a sale installment is overdue.'],
            'sale_completed' => ['module' => 'Sales', 'recipient' => 'Buyer and Landlord', 'when' => 'Every installment on a sale has been paid.'],
        ][$type] ?? null;
    }

    /**
     * Get the default template for a type
     */
    public static function getDefaultTemplate(string $type): array
    {
        return match($type) {
            'tenant_welcome' => [
                'sms_template' => 'Karibu {tenant_name} kwenye {company_name}. Lipia kodi kupitia control namba {control_number}. Ingia: {login_url} Username: {username} Password: {password}',
                'email_subject' => 'Welcome to {company_name}',
                'email_template' => "Hi {tenant_name},\n\nWelcome to {company_name}. Your control number is {control_number}.\n\nSign in at {login_url}\nUsername: {username}\nPassword: {password}\n\nYou can also sign in with your phone number. Please change your password after your first sign-in.",
            ],
            'landlord_welcome' => [
                'sms_template' => 'karibu {landlord_name} kwenye {company_name}. Umefanikiwa kujiunga na mfumo wetu kama mmiliki wa mali. Mali zako: {property_names}. Ingia: {login_url} Username: {username} Password: {password}',
                'email_subject' => 'Welcome to {company_name} - Landlord Registration',
                'email_template' => "Hi {landlord_name},\n\nWelcome to {company_name}. Your properties: {property_names}.\n\nSign in at {login_url}\nUsername: {username}\nPassword: {password}\n\nYou can also sign in with your phone number. Please change your password after your first sign-in.",
            ],
            'lease_landlord_notification' => [
                'sms_template' => 'Habari {landlord_name}, Unit {unit_number} katika {property_name} imeshikwa na {tenant_name} (Simu: {tenant_phone}). Mkataba unaanza {start_date} hadi {end_date}. Kodi ya mwezi ni Tsh {rent_amount}.',
                'email_subject' => 'Unit Rented - {property_name}',
                'email_template' => 'Unit rental notification',
            ],
            'buyer_welcome' => [
                'sms_template' => 'karibu {buyer_name} kwenye {company_name}. Control number yako ni {control_number}. Ingia: {login_url} Username: {username} Password: {password}',
                'email_subject' => 'Welcome to {company_name}',
                'email_template' => "Hi {buyer_name},\n\nWelcome to {company_name}. Your control number is {control_number}.\n\nSign in at {login_url}\nUsername: {username}\nPassword: {password}\n\nYou can also sign in with your phone number. Please change your password after your first sign-in.",
            ],
            'staff_welcome' => [
                'sms_template' => 'Karibu {staff_name} kwenye {company_name}. Ingia: {login_url} Username: {username} Password: {password}',
                'email_subject' => 'Your {company_name} account',
                'email_template' => "Hi {staff_name},\n\nAn account has been created for you at {company_name}.\n\nSign in at {login_url}\nUsername: {username}\nPassword: {password}\n\nYou can also sign in with your phone number. Please change your password after your first sign-in.",
            ],
            'lease_payment' => [
                'sms_template' => 'Habari {tenant_name}, invoice mpya ya Tsh {amount} kwa ajili ya Unit {unit_number} katika {property_name} imetolewa, inatakiwa kulipwa tarehe {due_date}. Balance yako ni Tsh {balance}. Asante.',
                'email_subject' => 'New Invoice - Unit {unit_number}, due {due_date}',
                'email_template' => 'A new invoice of Tsh {amount} for Unit {unit_number} at {property_name} is due on {due_date}. Balance: Tsh {balance}.',
            ],
            'buyer_bill' => [
                'sms_template' => 'Habari {buyer_name}, invoice mpya {invoice_number} ya Tsh {amount} kwa ajili ya Unit {unit_number} katika {property_name} imetolewa, inatakiwa kulipwa tarehe {due_date}. Balance yako ni Tsh {balance}. Asante.',
                'email_subject' => 'New Invoice {invoice_number} - Unit {unit_number}, due {due_date}',
                'email_template' => 'A new invoice {invoice_number} of Tsh {amount} for Unit {unit_number} at {property_name} is due on {due_date}. Balance: Tsh {balance}.',
            ],
            'buyer_invoice' => [
                'sms_template' => 'Tumepokea malipo yako ya Tsh {amount} ya awamu ya {installment_number} kati ya {installments_total} (Unit {unit_number}, {property_name}) tarehe {payment_date}. Kilichobaki kwenye awamu hii: Tsh {installment_balance}. Jumla iliyobaki: Tsh {balance}, awamu {installments_remaining} zimebaki. Asante.',
                'email_subject' => 'Payment received - Installment {installment_number} of {installments_total}',
                'email_template' => 'Hi {buyer_name},

We received your payment of Tsh {amount} on {payment_date} for installment {installment_number} of {installments_total} (Unit {unit_number} at {property_name}).

Remaining on this installment: Tsh {installment_balance}
Total remaining: Tsh {balance}
Installments remaining: {installments_remaining}

Thank you.',
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
            'invoice_due_soon' => [
                'sms_template' => 'Kumbusho: Invoice {invoice_number} ya Tsh {amount} kwa ajili ya Unit {unit_number} katika {property_name} inatakiwa kulipwa tarehe {due_date}.',
                'email_subject' => 'Rent due {due_date} - Invoice {invoice_number}',
                'email_template' => 'Hi {tenant_name},

A reminder that invoice {invoice_number} for Tsh {amount} at {property_name}, Unit {unit_number} is due on {due_date}.

Please arrange payment before then.',
            ],
            'invoice_overdue' => [
                'sms_template' => 'Invoice {invoice_number} ya Tsh {amount} kwa ajili ya Unit {unit_number} katika {property_name} ilitakiwa kulipwa tarehe {due_date} na bado haijalipwa.',
                'email_subject' => 'Overdue - Invoice {invoice_number}',
                'email_template' => 'Hi {tenant_name},

Invoice {invoice_number} for Tsh {amount} at {property_name}, Unit {unit_number} was due on {due_date} and remains unpaid.

Please arrange payment as soon as possible.',
            ],
            'installment_due_soon' => [
                'sms_template' => 'Kumbusho: Installment #{installment_number} ya Tsh {amount} kwa ajili ya Unit {unit_number} katika {property_name} inatakiwa kulipwa tarehe {due_date}.',
                'email_subject' => 'Payment due {due_date} - Installment #{installment_number}',
                'email_template' => 'Hi {buyer_name},

A reminder that installment #{installment_number} for Tsh {amount} at {property_name}, Unit {unit_number} is due on {due_date}.

Please arrange payment before then.',
            ],
            'installment_overdue' => [
                'sms_template' => 'Installment #{installment_number} ya Tsh {amount} kwa ajili ya Unit {unit_number} katika {property_name} ilitakiwa kulipwa tarehe {due_date} na bado haijalipwa.',
                'email_subject' => 'Overdue - Installment #{installment_number}',
                'email_template' => 'Hi {buyer_name},

Installment #{installment_number} for Tsh {amount} at {property_name}, Unit {unit_number} was due on {due_date} and remains unpaid.

Please arrange payment as soon as possible.',
            ],
            'sale_completed' => [
                'sms_template' => 'Malipo yote ya Unit {unit_number} katika {property_name} yamekamilika (mnunuzi: {buyer_name}). Jumla iliyolipwa: Tsh {total_paid}. Asante!',
                'email_subject' => 'Sale completed - {unit_number}',
                'email_template' => 'The sale for Unit {unit_number} at {property_name} has been completed - all installments have been paid (buyer: {buyer_name}). Total price: Tsh {total_price}. Total paid: Tsh {total_paid}. Thank you for your business!',
            ],
            'sale_created' => [
                'sms_template' => 'Habari {buyer_name}, umenunua Unit {unit_number} katika {property_name}. Bei ya jumla: Tsh {total_price}. Umelipa: Tsh {total_paid}. Kilichobaki: Tsh {balance}. Malipo ni awamu {installments_total} za Tsh {installment_amount}, kuanzia {first_due_date} hadi {last_due_date}. Asante.',
                'email_subject' => 'Purchase details - Unit {unit_number}, {property_name}',
                'email_template' => 'Hi {buyer_name},

Thank you for your purchase of Unit {unit_number} at {property_name}.

Total price: Tsh {total_price}
Paid so far: Tsh {total_paid}
Balance: Tsh {balance}

Payment plan: {installments_total} installments of Tsh {installment_amount}, from {first_due_date} to {last_due_date}.',
            ],
            'sale_payment_landlord' => [
                'sms_template' => 'Umepokea malipo ya Tsh {amount} kutoka kwa {buyer_name} - awamu ya {installment_number} kati ya {installments_total}, Unit {unit_number} katika {property_name} ({payment_date}). Iliyobaki kulipwa: Tsh {balance}, awamu {installments_remaining} zimebaki. Asante.',
                'email_subject' => 'Payment received from {buyer_name} - Unit {unit_number}',
                'email_template' => 'Payment of Tsh {amount} received from {buyer_name} on {payment_date} for installment {installment_number} of {installments_total} (Unit {unit_number} at {property_name}).

Total remaining: Tsh {balance}
Installments remaining: {installments_remaining}',
            ],
            'lease_created_tenant' => [
                'sms_template' => 'Karibu {tenant_name}. Mkataba wako wa Unit {unit_number} katika {property_name} unaanza {start_date} hadi {end_date}. Kodi ni Tsh {rent_amount}. Lipia kupitia control number {control_number}, bank {bank_name}, jina la akaunti {account_name}, a/c {account_number}. Asante.',
                'email_subject' => 'Your lease - Unit {unit_number}, {property_name}',
                'email_template' => 'Hi {tenant_name},

Your lease for Unit {unit_number} at {property_name} runs from {start_date} to {end_date}.

Rent: Tsh {rent_amount}
Deposit: Tsh {deposit_amount}

Pay via control number {control_number}, bank {bank_name}, account name {account_name}, account number {account_number}.

Thank you.',
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
