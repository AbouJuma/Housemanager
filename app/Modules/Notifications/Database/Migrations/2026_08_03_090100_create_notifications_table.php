<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laravel's standard notifications table shape (config('notifications')
 * has no dedicated config file - the 'database' channel and the
 * Notifiable trait resolve this by convention). No company_id column,
 * unlike Module 5's activity_log: notifications are inherently
 * per-user (notifiable_id already implies a company via that user),
 * and there's no concrete cross-company "notification log" screen in
 * this module's scope to justify one - add it if/when one exists,
 * not preemptively.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
