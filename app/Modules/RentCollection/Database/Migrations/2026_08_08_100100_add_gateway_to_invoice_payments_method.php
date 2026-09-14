<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uses Blueprint::change() rather than a raw ALTER TABLE statement
     * so this also runs under the sqlite connection the test suite
     * uses (phpunit.xml) - Laravel 12 no longer needs doctrine/dbal for
     * this on either driver (SQLite's grammar recreates the table
     * under the hood). 'gateway' distinguishes an online,
     * RentCollection-recorded payment from a manually recorded one
     * (cash/bank_transfer/etc.) without a separate boolean column.
     */
    public function up(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table): void {
            $table->enum('method', ['cash', 'bank_transfer', 'mobile_money', 'cheque', 'gateway', 'other'])->default('cash')->change();
        });
    }

    public function down(): void
    {
        Schema::table('invoice_payments', function (Blueprint $table): void {
            $table->enum('method', ['cash', 'bank_transfer', 'mobile_money', 'cheque', 'other'])->default('cash')->change();
        });
    }
};
