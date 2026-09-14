<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Spatie's activity_log table has no tenant column of its own;
        // without one, listing "this company's activity" would mean
        // scanning every row and inspecting subject/causer relations
        // per row. Adding it directly (auto-filled by
        // ActivityLogEntry::booted(), see the model) keeps audit-log
        // queries as fast as everything else in this app.
        Schema::table(config('activitylog.table_name'), function (Blueprint $table): void {
            $table->foreignId('company_id')->nullable()->after('id')
                ->constrained('companies')->nullOnDelete();
            $table->index(['company_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table(config('activitylog.table_name'), function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
        });
    }
};
