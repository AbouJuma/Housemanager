<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['individual', 'company'])->default('individual');
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->string('secondary_phone', 32)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('identification_type', ['national_id', 'passport', 'driving_license', 'voter_id', 'other'])->nullable();
            // text, not string: Laravel's `encrypted` cast produces
            // ciphertext considerably longer than the plaintext, and
            // encrypted, not plain, for the same reason as
            // landlords.bank_details - a national ID/passport number is
            // sensitive PII, protected at rest against DB-level exposure
            // (backup theft, an SQL dump) without being masked from the
            // company that legitimately needs to reference it (lease
            // documents, identity verification).
            $table->text('identification_number')->nullable();
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code', 32)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 32)->nullable();
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
