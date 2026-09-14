<?php

namespace Tests\Unit\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\Concerns\CreatesCompanyScopedWidgetsTable;
use Tests\Support\Models\CompanyScopedWidget;
use Tests\TestCase;

class HasUuidTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCompanyScopedWidgetsTable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createCompanyScopedWidgetsTable();
    }

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $widget = CompanyScopedWidget::create(['name' => 'Generator']);

        $this->assertTrue(Str::isUuid($widget->uuid));
    }

    public function test_an_explicit_uuid_is_preserved(): void
    {
        $uuid = (string) Str::uuid();

        $widget = CompanyScopedWidget::create(['name' => 'Generator', 'uuid' => $uuid]);

        $this->assertSame($uuid, $widget->uuid);
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new CompanyScopedWidget())->getRouteKeyName());
    }
}
