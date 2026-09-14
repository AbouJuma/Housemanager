<?php

namespace Tests\Unit\Core;

use App\Core\Foundation\ModuleManager;
use Tests\TestCase;

class ModuleManagerTest extends TestCase
{
    public function test_it_discovers_module_providers_by_convention(): void
    {
        $providers = ModuleManager::discover(__DIR__.'/../../Support/fixtures/Modules');

        $this->assertSame(
            ['App\\Modules\\Billing\\Providers\\BillingServiceProvider'],
            $providers
        );
    }

    public function test_it_ignores_module_folders_without_a_matching_provider(): void
    {
        $providers = ModuleManager::discover(__DIR__.'/../../Support/fixtures/EmptyModules');

        $this->assertSame([], $providers);
    }

    public function test_it_returns_an_empty_array_for_a_missing_directory(): void
    {
        $this->assertSame([], ModuleManager::discover(__DIR__.'/../../Support/fixtures/DoesNotExist'));
    }
}
