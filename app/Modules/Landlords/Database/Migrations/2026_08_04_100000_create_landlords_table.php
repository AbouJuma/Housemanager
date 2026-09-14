<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landlords', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['individual', 'company'])->default('individual');
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
            // text, not json: Laravel's encrypted:array cast encrypts the
            // whole JSON-encoded string, and ciphertext isn't valid JSON
            // (same reasoning as integrations.credentials, Module 7).
            // Unlike that column, this holds the company's OWN reference
            // data about its landlord (bank_name/account_name/
            // account_number/branch/swift_code) - legitimately viewable
            // by an authorized company admin, not a write-only third-
            // party secret - so it's encrypted at rest against DB-level
            // exposure, not masked from the owning company in the UI.
            $table->text('bank_details')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landlords');
    }
};
