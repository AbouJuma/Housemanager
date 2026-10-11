<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Widens message_templates.type for 5 notifications that used to
     * send hardcoded SMS/email text instead of going through a
     * MessageTemplate (App\Modules\RentCollection\Notifications\
     * InvoiceDueSoon/InvoiceOverdue, App\Modules\Sales\Notifications\
     * PaymentDueSoon/PaymentOverdue/SaleCompleted) - see each class's
     * own docblock. Same enum()->change() approach as
     * 2026_08_24_223738_add_lease_landlord_notification_template.php
     * (portable across MySQL and the SQLite test connection, unlike a
     * raw `ALTER TABLE ... MODIFY COLUMN`).
     */
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->enum('type', [
                'tenant_welcome', 'landlord_welcome', 'buyer_welcome', 'lease_payment', 'buyer_bill',
                'buyer_invoice', 'landlord_payment_notification', 'tenant_payment_notification',
                'lease_landlord_notification', 'invoice_due_soon', 'invoice_overdue',
                'installment_due_soon', 'installment_overdue', 'sale_completed',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->enum('type', [
                'tenant_welcome', 'landlord_welcome', 'buyer_welcome', 'lease_payment', 'buyer_bill',
                'buyer_invoice', 'landlord_payment_notification', 'tenant_payment_notification',
                'lease_landlord_notification',
            ])->change();
        });
    }
};
