<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new buyer_id column referencing buyers table (using a different approach)
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('new_buyer_id')->nullable()->after('buyer_id')->constrained('buyers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropForeign(['new_buyer_id']);
            $table->dropColumn('new_buyer_id');
        });
    }
};
