<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['individual', 'company'])->default('company');
            $table->enum('category', [
                'plumbing', 'electrical', 'hvac', 'cleaning', 'landscaping',
                'pest_control', 'general_contractor', 'utility_provider', 'security', 'other',
            ])->default('other');
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('secondary_phone', 32)->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('tax_id')->nullable();
            // Same encrypted-but-not-masked reasoning as landlords.bank_details
            // (ARCHITECTURE.md §10b) - the company's own reference data
            // about who it pays, not a write-only third-party secret.
            $table->text('bank_details')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
