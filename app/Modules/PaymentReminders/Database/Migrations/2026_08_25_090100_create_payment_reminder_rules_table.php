<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_reminder_rules', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            // 'rent' (tenant invoices, via RentCollection) or
            // 'sale_installment' (buyer installments, via Sales) - both
            // contexts share this one rule engine/table rather than each
            // module inventing its own.
            $table->string('context');
            // Positive = days before due date, 0 = on the due date,
            // negative = days after due date (overdue).
            $table->integer('offset_days');
            // Null = fire once at offset_days. Set = keep re-firing
            // every N days after the first match (used for recurring
            // overdue nudges).
            $table->unsignedInteger('repeat_every_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'context', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reminder_rules');
    }
};
