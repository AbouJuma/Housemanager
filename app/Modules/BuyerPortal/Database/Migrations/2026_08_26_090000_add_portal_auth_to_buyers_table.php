<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buyers', function (Blueprint $table): void {
            // Nullable - most buyers never get portal access at all.
            // Deliberately absent from Buyer::$fillable (same discipline
            // as tenants.password/landlords.password) - only
            // BuyerPortalService::enableAccess()/disableAccess() ever
            // set it, via forceFill().
            $table->string('password')->nullable()->after('email');
            $table->rememberToken()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('buyers', function (Blueprint $table): void {
            $table->dropColumn(['password', 'remember_token']);
        });
    }
};
