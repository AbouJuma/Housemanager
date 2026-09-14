<?php

namespace Tests\Unit\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\Concerns\CreatesCompanyScopedWidgetsTable;
use Tests\Support\Models\CompanyScopedWidget;
use Tests\Support\Repositories\CompanyScopedWidgetRepository;
use Tests\Support\Services\CompanyScopedWidgetService;
use Tests\TestCase;

class BaseRepositoryServiceTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCompanyScopedWidgetsTable;

    protected CompanyScopedWidgetRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createCompanyScopedWidgetsTable();

        $this->repository = new CompanyScopedWidgetRepository(new CompanyScopedWidget());
    }

    public function test_repository_creates_finds_updates_and_deletes(): void
    {
        $widget = $this->repository->create(['name' => 'Generator']);

        $this->assertNotNull($widget->id);
        $this->assertSame($widget->id, $this->repository->find($widget->id)->id);
        $this->assertSame($widget->uuid, $this->repository->findByUuid($widget->uuid)->uuid);

        $updated = $this->repository->update($widget, ['name' => 'Renamed Generator']);
        $this->assertSame('Renamed Generator', $updated->fresh()->name);

        $this->assertTrue($this->repository->delete($updated));
        $this->assertNull($this->repository->find($widget->id));
    }

    public function test_repository_all_and_paginate(): void
    {
        CompanyScopedWidget::create(['name' => 'One']);
        CompanyScopedWidget::create(['name' => 'Two']);

        $this->assertCount(2, $this->repository->all());
        $this->assertSame(2, $this->repository->paginate(10)->total());
    }

    public function test_service_wraps_repository_calls_in_a_transaction(): void
    {
        $service = new CompanyScopedWidgetService($this->repository);
        $baselineTransactionLevel = DB::transactionLevel();

        $widget = $service->create(['name' => 'Transactional Widget']);

        $this->assertDatabaseHas('company_scoped_widgets', ['name' => 'Transactional Widget']);
        $this->assertSame($baselineTransactionLevel, DB::transactionLevel());

        $service->update($widget, ['name' => 'Renamed']);
        $this->assertDatabaseHas('company_scoped_widgets', ['name' => 'Renamed']);

        $this->assertTrue($service->delete($widget));
        $this->assertDatabaseMissing('company_scoped_widgets', ['name' => 'Renamed']);
    }
}
