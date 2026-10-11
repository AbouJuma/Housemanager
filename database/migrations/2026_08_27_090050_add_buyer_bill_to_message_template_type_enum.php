<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * See 2026_08_24_223738_add_lease_landlord_notification_template.php's
     * docblock - enum()->change() is the cross-driver-portable way to
     * widen this column, unlike the raw MySQL-only ALTER this migration
     * used before (which broke the SQLite-backed test suite entirely).
     */
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->enum('type', [
                'tenant_welcome', 'landlord_welcome', 'buyer_welcome', 'lease_payment', 'buyer_bill',
                'buyer_invoice', 'landlord_payment_notification', 'tenant_payment_notification',
                'lease_landlord_notification',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->enum('type', [
                'tenant_welcome', 'landlord_welcome', 'buyer_welcome', 'lease_payment',
                'buyer_invoice', 'landlord_payment_notification', 'tenant_payment_notification',
                'lease_landlord_notification',
            ])->change();
        });
    }
};
