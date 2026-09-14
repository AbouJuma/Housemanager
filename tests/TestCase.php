<?php

namespace Tests;

use App\Modules\Users\Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * The permission catalog is effectively reference data every
     * environment needs to exist before Spatie Permission's
     * hasPermissionTo() checks work at all - it throws (not false) for
     * an unregistered permission name.
     *
     * This can't be a RefreshDatabase::afterRefreshingDatabase()
     * override: PHP resolves a trait's own method over one inherited
     * from a parent class, so RefreshDatabase's empty stub always wins
     * and a parent-class override here is silently never called.
     * Seeding after parent::setUp() (which runs RefreshDatabase's own
     * setup) is what actually runs on every test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (Schema::hasTable('permissions')) {
            (new PermissionSeeder())->run();
        }
    }
}
