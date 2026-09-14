<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_payment_attempts', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            // Matches integrations.provider, e.g. 'stripe'/'mpesa' - not
            // a FK to integrations.id, since the integration a company
            // used could later be deactivated/reconfigured and this is
            // an immutable record of what was used at the time.
            $table->string('provider');
            // The gateway's own transaction/session reference -
            // App\Modules\Payments\Contracts\PaymentGatewayInterface's
            // charge()/verify() currency, both keyed off this string.
            $table->string('reference')->nullable();
            // Integer minor units, matching PaymentGatewayInterface's
            // convention - this is the integration boundary Module 13's
            // ARCHITECTURE.md note pointed to for the decimal-to-minor-
            // unit conversion (see App\Modules\RentCollection\Services\
            // RentCollectionService).
            $table->unsignedBigInteger('amount_minor');
            $table->char('currency', 3);
            $table->enum('status', ['pending', 'successful', 'failed'])->default('pending');
            $table->text('message')->nullable();
            $table->foreignId('initiated_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index('invoice_id');
            $table->index(['provider', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payment_attempts');
    }
};
