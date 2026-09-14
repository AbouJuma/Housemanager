<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payments', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->date('paid_on');
            $table->enum('method', ['cash', 'bank_transfer', 'mobile_money', 'cheque', 'other'])->default('cash');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            // Who recorded this - nullOnDelete, not restrictOnDelete:
            // this is an audit attribution, not a data dependency like
            // invoices.lease_id - the payment record must survive the
            // recording user's account being deleted later.
            $table->foreignId('recorded_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('invoice_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }
};
