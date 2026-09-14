<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            // Nullable, not BelongsToCompany: null means "system-wide"
            // (SaaS-owner scope), not "unassigned" - a different meaning
            // than every other nullable company_id in this app, so it's
            // handled explicitly in SettingsService rather than via the
            // trait's global-scope-plus-auto-stamp behavior.
            $table->foreignId('company_id')->nullable()->constrained('companies')->cascadeOnDelete();
            $table->string('key');
            $table->json('value')->nullable();
            $table->timestamps();

            // Not a DB-level uniqueness guarantee: MySQL allows multiple
            // NULLs under a composite unique index, so two system-wide
            // rows for the same key wouldn't be rejected by this alone.
            // SettingsService::set() uses updateOrCreate() and is the
            // real guard against duplicates; this index is for lookup
            // performance.
            $table->index(['company_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
