<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            // Add new template types to the enum
            $table->enum('type', [
                'tenant_welcome',
                'landlord_welcome',
                'buyer_welcome',
                'lease_payment',
                'buyer_invoice',
                'landlord_payment_notification',
                'tenant_payment_notification'
            ])->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->enum('type', [
                'tenant_welcome',
                'landlord_welcome',
                'buyer_welcome',
                'lease_payment',
                'buyer_invoice'
            ])->change();
        });
    }
};
