<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transactions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // restrictOnDelete - same "protect real historical ledger
            // rows" reasoning as journal_entry_lines.account_id
            // (ARCHITECTURE.md §12) - an item with real stock movement
            // history can never be hard-deleted out from under it.
            $table->foreignId('inventory_item_id')->constrained()->restrictOnDelete();
            $table->enum('type', ['receipt', 'usage', 'adjustment']);
            // Signed delta actually applied - positive for receipt,
            // negative for usage, either sign for adjustment. Never
            // recomputed from quantity_before/after; it's the fact
            // being recorded, they're the snapshot around it.
            $table->decimal('quantity', 12, 2);
            $table->decimal('quantity_before', 12, 2);
            $table->decimal('quantity_after', 12, 2);
            // Direct FK, not a deferred one - Maintenance (Module 22)
            // already exists by the time this module is built. Set only
            // when a `usage` transaction is consumed by a specific
            // repair - see ARCHITECTURE.md §16.
            $table->foreignId('maintenance_request_id')->nullable()->constrained()->nullOnDelete();
            $table->text('reason')->nullable();
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('transaction_date');
            $table->timestamps();
            // No softDeletes - an inventory transaction is an immutable,
            // append-only ledger row, the same discipline as
            // journal_entries (ARCHITECTURE.md §12). Correcting a
            // mistake means logging a new offsetting `adjustment`
            // transaction, never editing or removing this one.

            $table->index(['company_id', 'inventory_item_id']);
            $table->index('maintenance_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
    }
};
