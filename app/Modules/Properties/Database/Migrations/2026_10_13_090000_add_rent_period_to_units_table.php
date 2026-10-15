<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a unit's rent_amount is quoted per - a month, quarter, half-year
 * or year. Leases convert it to their own billing cycle
 * (App\Modules\Leases\Support\RentPeriods). Existing units stay monthly.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->string('rent_period', 20)->default('monthly')->after('rent_amount');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table): void {
            $table->dropColumn('rent_period');
        });
    }
};
