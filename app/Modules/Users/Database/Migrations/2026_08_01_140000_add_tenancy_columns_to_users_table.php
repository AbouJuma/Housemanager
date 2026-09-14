<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // Nullable at the DB level (MySQL allows multiple NULLs under a
            // UNIQUE index) so this migration doesn't need doctrine/dbal to
            // retroactively tighten it; App\Core\Concerns\HasUuid guarantees
            // every row created through Eloquent from here on has one.
            $table->uuid('uuid')->unique()->nullable()->after('id');
            $table->foreignId('company_id')->nullable()->after('uuid')
                ->constrained('companies')->nullOnDelete();
        });

        DB::table('users')->whereNull('uuid')->orderBy('id')->each(function ($user): void {
            DB::table('users')->where('id', $user->id)->update(['uuid' => (string) Str::uuid()]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn('uuid');
        });
    }
};
