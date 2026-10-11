<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_logs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            // Nullable: a notifiable with no company (e.g. SaaS-owner
            // staff, App\Models\User with company_id null) can still be
            // notified - see App\Modules\Notifications\Notifications\
            // TrialEndingSoon.
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('notifiable_type');
            $table->unsignedBigInteger('notifiable_id');
            $table->string('channel');
            $table->string('recipient');
            $table->string('message_type');
            $table->string('status');
            $table->string('provider')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'created_at']);
            $table->index(['notifiable_type', 'notifiable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_logs');
    }
};
