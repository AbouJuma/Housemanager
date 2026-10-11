<?php

use App\Models\MessageTemplate;
use Illuminate\Database\Migrations\Migration;

/**
 * Data patch, not a schema change: `lease_payment` was seeded but never
 * rendered by anything (an orphan template) until this deploy wired it
 * up for real - as a "new invoice" notice, not its old dormant
 * "payment received" copy. Existing installations already have that
 * old copy stored in the row (MessageTemplateSeeder's firstOrCreate
 * only inserts once, it doesn't update), so this patches it directly.
 * Also seeds the new `buyer_bill` shared template, the buyer-side
 * equivalent.
 */
return new class extends Migration
{
    public function up(): void
    {
        MessageTemplate::whereNull('company_id')
            ->where('type', 'lease_payment')
            ->where('name', 'Lease Payment Confirmation')
            ->update([
                'name' => 'Lease Invoice Notice',
                'sms_template' => 'Habari {tenant_name}, invoice mpya ya Tsh {amount} kwa ajili ya Unit {unit_number} katika {property_name} imetolewa, inatakiwa kulipwa tarehe {due_date}. Balance yako ni Tsh {balance}. Asante.',
                'email_subject' => 'New Invoice - Unit {unit_number}, due {due_date}',
                'email_template' => 'A new invoice of Tsh {amount} for Unit {unit_number} at {property_name} is due on {due_date}. Balance: Tsh {balance}.',
            ]);

        MessageTemplate::firstOrCreate(
            ['company_id' => null, 'type' => 'buyer_bill'],
            [
                'name' => 'Buyer Invoice Notice',
                'sms_template' => 'Habari {buyer_name}, invoice mpya ya Tsh {amount} (installment #{installment_number}) kwa ajili ya Unit {unit_number} katika {property_name} imetolewa, inatakiwa kulipwa tarehe {due_date}. Balance yako ni Tsh {balance}. Asante.',
                'email_subject' => 'New Invoice - Unit {unit_number}, due {due_date}',
                'email_template' => 'A new installment invoice of Tsh {amount} for Unit {unit_number} at {property_name} is due on {due_date}. Balance: Tsh {balance}.',
                'is_active' => true,
            ]
        );
    }

    public function down(): void
    {
        // Deliberately not reverted - a company may have since customized
        // either row through the Message Templates admin screen, and
        // rolling back a data patch onto since-edited content would
        // silently discard that customization. down() intentionally
        // no-ops, the same restraint as other data-patch migrations.
    }
};
