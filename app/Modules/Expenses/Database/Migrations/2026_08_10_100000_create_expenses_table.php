<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // restrictOnDelete - same reasoning as journal_entry_lines.account_id
            // (ARCHITECTURE.md §12): an account with real expense
            // history can't be hard-deleted out from under it.
            $table->foreignId('account_id')->constrained()->restrictOnDelete();
            // Optional - some expenses are company-wide (a software
            // subscription, a bank fee), not tied to one property.
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            // Free text, not a vendor_id FK - the Vendors module
            // (Module 21) doesn't exist yet. Deferred-FK pattern, same
            // discipline as properties.landlord_id being added a
            // module after Properties shipped (ARCHITECTURE.md §10b) -
            // a real vendor_id column arrives when Vendors does.
            $table->string('vendor_name')->nullable();
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->enum('payment_status', ['unpaid', 'paid'])->default('unpaid');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque', 'mobile_money', 'other'])->nullable();
            $table->date('paid_on')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            // 'active'/'voided' - separate from payment_status, which
            // tracks whether the bill has been settled. status tracks
            // whether the record itself still counts - see
            // ExpenseService::void(), ARCHITECTURE.md §13.
            $table->enum('status', ['active', 'voided'])->default('active');
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'expense_date']);
            $table->index('property_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
