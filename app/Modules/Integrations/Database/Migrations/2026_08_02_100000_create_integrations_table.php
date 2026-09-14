<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('provider');
            $table->string('mode')->default('sandbox');
            $table->boolean('is_active')->default(false);
            // text, not json: Laravel's encrypted:array cast encrypts the
            // whole JSON-encoded string, and ciphertext isn't valid JSON.
            $table->text('credentials')->nullable();
            $table->string('webhook_url')->nullable();
            $table->string('callback_url')->nullable();
            $table->string('status')->default('not_configured');
            $table->timestamp('last_tested_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'type', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
