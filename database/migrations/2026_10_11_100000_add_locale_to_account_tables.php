<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each person's chosen interface language (English / Kiswahili) - set
 * from the language switcher, see App\Http\Middleware\SetLocale.
 * Null = use their company's default language.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['users', 'tenants', 'landlords', 'buyers'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->string('locale', 5)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach (['users', 'tenants', 'landlords', 'buyers'] as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropColumn('locale');
            });
        }
    }
};
