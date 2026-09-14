<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            // Nullable - most tenants never get portal access at all.
            // Deliberately absent from Tenant::$fillable (the same
            // "system-derived, only touched by its owning service"
            // discipline as inventory_items.quantity_on_hand,
            // ARCHITECTURE.md §16) - only TenantPortalService::
            // enableAccess()/resetAccess() ever set it, via forceFill().
            $table->string('password')->nullable()->after('email');
            $table->rememberToken()->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table): void {
            $table->dropColumn(['password', 'remember_token']);
        });
    }
};
