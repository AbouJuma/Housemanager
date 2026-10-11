<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Neighbourhood / ward below the district - see config/tanzania.php.
 * A property's region lives in `state` and its district in `city`;
 * the index backs the property list's region/district filters.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->string('area')->nullable()->after('city');
            $table->index(['company_id', 'state', 'city']);
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'state', 'city']);
            $table->dropColumn('area');
        });
    }
};
