<?php

use App\Core\Auth\LoginIdentity;
use App\Models\User;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Lets staff, tenants, landlords and buyers sign in with a username or
 * phone number, not just an email (see App\Core\Auth\LoginIdentity).
 * Existing rows get a username generated from their name and their
 * phone digits copied into login_phone.
 */
return new class extends Migration
{
    /** @var array<string, class-string> */
    protected array $tables = [
        'users' => User::class,
        'tenants' => Tenant::class,
        'landlords' => Landlord::class,
        'buyers' => Buyer::class,
    ];

    public function up(): void
    {
        foreach ($this->tables as $table => $model) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->string('username', 60)->nullable()->unique()->after('name');
                $blueprint->string('login_phone', 32)->nullable()->index()->after(
                    Schema::hasColumn($table, 'phone') ? 'phone' : 'email'
                );
            });

            DB::table($table)->orderBy('id')->select(['id', 'name', 'phone'])->chunkById(200, function ($rows) use ($table, $model): void {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update([
                        'username' => LoginIdentity::uniqueUsername($model, $row->name, $row->id),
                        'login_phone' => LoginIdentity::phoneDigits($row->phone),
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->tables) as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropUnique(['username']);
                $blueprint->dropIndex(['login_phone']);
                $blueprint->dropColumn(['username', 'login_phone']);
            });
        }
    }
};
