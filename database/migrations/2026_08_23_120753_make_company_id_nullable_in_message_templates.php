<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['company_id']);
            
            // Drop the unique constraint on company_id and type
            $table->dropUnique(['company_id', 'type']);
            
            // Make company_id nullable
            $table->foreignId('company_id')->nullable()->change();
            
            // Add back the foreign key as nullable
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('message_templates', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['company_id']);
            
            // Make company_id not nullable again
            $table->foreignId('company_id')->nullable(false)->change();
            
            // Add back the unique constraint
            $table->unique(['company_id', 'type']);
            
            // Add back the foreign key as not nullable
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('cascade');
        });
    }
};
