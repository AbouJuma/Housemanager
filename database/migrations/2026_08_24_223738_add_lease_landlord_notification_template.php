<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Schema Blueprint's enum()->change() (native as of Laravel 11, no
     * doctrine/dbal needed) generates the correct DDL per driver - a
     * MySQL `ALTER ... MODIFY COLUMN` here, a full table rebuild on
     * SQLite - unlike the raw `ALTER TABLE ... MODIFY COLUMN` this
     * migration used before, which is MySQL-only syntax and threw a
     * syntax error against the SQLite connection PHPUnit's test suite
     * runs on, breaking every single feature test at migration time.
     */
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            $table->enum('type', [
                'tenant_welcome', 'landlord_welcome', 'buyer_welcome', 'lease_payment',
                'buyer_invoice', 'landlord_payment_notification', 'tenant_payment_notification',
                'lease_landlord_notification',
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
                'tenant_welcome', 'landlord_welcome', 'buyer_welcome', 'lease_payment',
                'buyer_invoice', 'landlord_payment_notification', 'tenant_payment_notification',
            ])->change();
        });
    }
};
