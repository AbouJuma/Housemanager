<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Deferred from Module 13 (see docs/ARCHITECTURE.md §10a) until the
 * Landlords module existed to answer "which landlord" - the same
 * discipline as Module 3 adding company_id/uuid to the pre-existing
 * users table, rather than speculatively scaffolding a foreign key to
 * a table that didn't exist yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->foreignId('landlord_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('landlord_id');
        });
    }
};
