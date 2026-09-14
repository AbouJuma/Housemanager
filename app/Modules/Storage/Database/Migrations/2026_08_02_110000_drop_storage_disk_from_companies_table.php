<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * companies.storage_disk (Module 2) was speculative scaffolding for
 * "which disk does this company use" - now that Module 8 actually
 * builds that resolution, it turned out a bare disk name isn't enough
 * (cloud providers need credentials too), so it's superseded by the
 * company's active "storage" row in `integrations` (Module 7) instead.
 * See App\Modules\Storage\Services\StorageResolver.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn('storage_disk');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('storage_disk')->default('local');
        });
    }
};
