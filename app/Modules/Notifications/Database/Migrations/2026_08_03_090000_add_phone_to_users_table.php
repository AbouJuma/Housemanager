<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Notifications is the first module needing a real destination for
 * SMS notifications - App\Modules\Notifications\Channels\CompanySmsChannel
 * needs somewhere to send to, so this gives `users` the field rather
 * than wiring the channel against a value that would always be empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('phone');
        });
    }
};
