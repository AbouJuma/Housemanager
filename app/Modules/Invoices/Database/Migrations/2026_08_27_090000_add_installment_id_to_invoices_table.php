<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            // Links a sale-generated invoice to the specific installment
            // it bills for, so a payment against that installment can
            // find and update the SAME invoice instead of creating a new
            // one each time (App\Modules\Sales\Services\SaleService
            // previously had no way to do this and just created a fresh
            // invoice per payment). Nullable - lease-linked invoices have
            // no installment at all.
            $table->foreignId('installment_id')->nullable()->after('sale_id')
                ->constrained('installments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('installment_id');
        });
    }
};
