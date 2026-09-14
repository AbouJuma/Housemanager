<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // restrictOnDelete - same "a plan referenced by real billing
            // history can never be hard-deleted out from under it"
            // reasoning as journal_entry_lines.account_id (§12);
            // deactivating a plan (is_active=false) is the correct way
            // to retire one, not deleting it.
            $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();
            $table->enum('status', ['trialing', 'active', 'past_due', 'canceled'])->default('trialing');
            $table->date('current_period_start');
            $table->date('current_period_end');
            $table->timestamp('canceled_at')->nullable();
            $table->timestamps();
            // No softDeletes - a subscription row is a real, immutable
            // billing period record, the same "never edit or erase real
            // history" instinct as journal_entries (§12) and
            // inventory_transactions (§16). Changing plans or renewing
            // creates a NEW row rather than mutating this one's plan/
            // dates; Company::currentSubscription() reads whichever row
            // has the latest current_period_start.

            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
