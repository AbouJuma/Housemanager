<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Companies were created with timezone 'UTC' by default, so dashboards
 * greeted East African users with "good morning" at 2pm. New companies
 * default to East Africa Time, and existing ones still on the untouched
 * UTC default move to it (an admin can change it under Settings ->
 * Company Profile).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('timezone')->default('Africa/Dar_es_Salaam')->change();
        });

        DB::table('companies')->where('timezone', 'UTC')->update(['timezone' => 'Africa/Dar_es_Salaam']);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table): void {
            $table->string('timezone')->default('UTC')->change();
        });
    }
};
