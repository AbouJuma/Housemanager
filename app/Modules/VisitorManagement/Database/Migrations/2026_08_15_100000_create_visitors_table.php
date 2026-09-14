<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitors', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // restrictOnDelete - a visit is a historical record ABOUT a
            // property, not a structural part of it, the same reasoning
            // as maintenance_requests.property_id (ARCHITECTURE.md §15).
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            // Who they're visiting, if it's a tenant - coexists with the
            // free-text host_name below, the same vendor_id/vendor_name
            // coexistence already established on expenses (ARCHITECTURE.md §14).
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            // Set when the visitor represents a vendor (e.g. a
            // contractor's rep arriving to do work) - optional, most
            // visits have no vendor connection at all.
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('visitor_name');
            $table->string('visitor_phone')->nullable();
            // Free-text fallback for who's being visited when it isn't
            // a tenant (building management, a landlord, staff).
            $table->string('host_name')->nullable();
            $table->enum('purpose', ['delivery', 'meeting', 'maintenance', 'interview', 'personal', 'other'])->default('other');
            $table->string('vehicle_plate')->nullable();
            $table->string('badge_number')->nullable();
            $table->dateTime('check_in_at');
            // Nullable - the single source of truth for whether a
            // visitor is still on-site, the same "one system-derived
            // timestamp, no redundant status enum" discipline as
            // leases.terminated_at (ARCHITECTURE.md §10d), simpler here
            // than Lease's own status column because a visit only ever
            // has two real states, not three.
            $table->dateTime('check_out_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('checked_in_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_out_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'check_in_at']);
            $table->index(['company_id', 'check_out_at']);
            $table->index('property_id');
            $table->index('unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
