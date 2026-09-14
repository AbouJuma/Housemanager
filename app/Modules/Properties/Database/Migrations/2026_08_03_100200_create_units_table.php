<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('building_id')->constrained()->cascadeOnDelete();
            $table->string('unit_number');
            $table->enum('type', [
                'apartment', 'studio', 'office', 'shop', 'warehouse_space', 'room', 'other',
            ]);
            $table->enum('status', ['vacant', 'occupied', 'under_maintenance', 'reserved'])->default('vacant');
            $table->unsignedTinyInteger('bedrooms')->nullable();
            $table->unsignedTinyInteger('bathrooms')->nullable();
            $table->decimal('area', 12, 2)->nullable();
            $table->string('area_unit', 10)->default('sqm');
            // Decimal, not integer minor-units: this is our own domain
            // storage (MySQL DECIMAL is exact, no float drift), not a
            // payment gateway payload - App\Modules\Payments\Contracts\
            // PaymentGatewayInterface's integer-cents convention applies
            // at the point a real charge is made (Rent Collection,
            // Module 18), not to how rent is stored here.
            $table->decimal('rent_amount', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'building_id']);
            $table->unique(['building_id', 'unit_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
