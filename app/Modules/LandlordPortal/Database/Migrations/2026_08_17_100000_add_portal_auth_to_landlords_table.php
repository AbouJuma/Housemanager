<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landlords', function (Blueprint $table): void {
            // Same deferred-column, absent-from-$fillable discipline as
            // tenants.password/remember_token (Module 26, ARCHITECTURE.md
            // §19) - only LandlordPortalService::enableAccess()/
            // disableAccess() ever touch these, via forceFill().
            $table->string('password')->nullable()->after('email');
            $table->rememberToken()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('landlords', function (Blueprint $table): void {
            $table->dropColumn(['password', 'remember_token']);
        });
    }
};
