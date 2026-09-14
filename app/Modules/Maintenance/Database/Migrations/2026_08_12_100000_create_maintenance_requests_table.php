<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_requests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // restrictOnDelete - same "protect real operational history"
            // reasoning as expenses.account_id/journal_entry_lines.account_id
            // (ARCHITECTURE.md §13/§12), not Building/Unit's cascadeOnDelete
            // from Property - a maintenance request is a historical
            // record ABOUT a property, not a structural part of it.
            $table->foreignId('property_id')->constrained()->restrictOnDelete();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('tenant_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            // Populated once a recorded cost is actually posted as an
            // Expense - see MaintenanceRequestService::recordExpense(),
            // ARCHITECTURE.md §15. Nullable, not every request costs
            // money or has had its cost recorded yet.
            $table->foreignId('expense_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            // Same category list as vendors.category (ARCHITECTURE.md
            // §14) so a request's category can be matched directly
            // against a vendor's, not a separate taxonomy needing a
            // mapping table.
            $table->enum('category', [
                'plumbing', 'electrical', 'hvac', 'cleaning', 'landscaping',
                'pest_control', 'general_contractor', 'utility_provider', 'security', 'other',
            ])->default('other');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'completed', 'cancelled'])->default('open');
            $table->date('reported_date');
            $table->date('scheduled_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->decimal('actual_cost', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('reported_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'priority']);
            $table->index('property_id');
            $table->index('unit_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
