<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // Nullable - not every item belongs to one property (bulk
            // cleaning supplies, a shared tool pool); same reasoning as
            // expenses.property_id (ARCHITECTURE.md §13).
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            // Preferred/last supplier - optional reference, not a
            // structural dependency.
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('sku')->nullable();
            $table->enum('category', [
                'appliance', 'furniture', 'tool', 'plumbing_supply', 'electrical_supply',
                'cleaning_supply', 'hardware', 'safety_equipment', 'other',
            ])->default('other');
            $table->enum('unit_of_measure', ['piece', 'box', 'liter', 'kilogram', 'meter', 'roll', 'pack', 'other'])->default('piece');
            // System-derived - never set directly by a create/update
            // request, only ever changed by InventoryItemService::
            // recordTransaction(). See ARCHITECTURE.md §16.
            $table->decimal('quantity_on_hand', 12, 2)->default(0);
            $table->decimal('reorder_level', 12, 2)->nullable();
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->enum('status', ['active', 'discontinued'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'category']);
            $table->index('property_id');
            $table->index('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
