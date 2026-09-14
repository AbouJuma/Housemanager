<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deliberately deferred from Module 20 until this module existed to
 * answer "which vendor" - the same discipline Module 14 applied adding
 * properties.landlord_id only once Landlords existed (ARCHITECTURE.md
 * §10b). expenses.vendor_name (free text) is kept, not replaced - see
 * ARCHITECTURE.md §14 for why both coexist.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->foreignId('vendor_id')->nullable()->after('account_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('vendor_id');
        });
    }
};
