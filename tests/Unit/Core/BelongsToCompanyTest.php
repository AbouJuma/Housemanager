<?php

namespace Tests\Unit\Core;

use App\Core\Tenancy\CompanyContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\Concerns\CreatesCompanyScopedWidgetsTable;
use Tests\Support\Models\CompanyScopedWidget;
use Tests\TestCase;

class BelongsToCompanyTest extends TestCase
{
    use RefreshDatabase;
    use CreatesCompanyScopedWidgetsTable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createCompanyScopedWidgetsTable();
    }

    public function test_new_records_are_stamped_with_the_current_company(): void
    {
        app(CompanyContext::class)->set(42);

        $widget = CompanyScopedWidget::create(['name' => 'Generator']);

        $this->assertSame(42, $widget->company_id);
    }

    public function test_an_explicit_company_id_is_not_overwritten(): void
    {
        app(CompanyContext::class)->set(42);

        $widget = CompanyScopedWidget::create(['name' => 'Generator', 'company_id' => 99]);

        $this->assertSame(99, $widget->company_id);
    }

    public function test_queries_are_scoped_to_the_current_company(): void
    {
        app(CompanyContext::class)->set(1);
        CompanyScopedWidget::create(['name' => 'Company One Widget']);

        app(CompanyContext::class)->set(2);
        CompanyScopedWidget::create(['name' => 'Company Two Widget']);

        $this->assertSame(1, CompanyScopedWidget::count());
        $this->assertSame('Company Two Widget', CompanyScopedWidget::first()->name);
    }

    public function test_for_company_scope_bypasses_the_current_tenant(): void
    {
        app(CompanyContext::class)->set(1);
        CompanyScopedWidget::create(['name' => 'Company One Widget']);

        app(CompanyContext::class)->set(2);
        CompanyScopedWidget::create(['name' => 'Company Two Widget']);

        $this->assertSame(1, CompanyScopedWidget::forCompany(1)->count());
    }

    public function test_without_a_current_company_all_records_are_visible(): void
    {
        app(CompanyContext::class)->set(1);
        CompanyScopedWidget::create(['name' => 'Company One Widget']);

        app(CompanyContext::class)->set(2);
        CompanyScopedWidget::create(['name' => 'Company Two Widget']);

        app(CompanyContext::class)->clear();

        $this->assertSame(2, CompanyScopedWidget::count());
    }
}
