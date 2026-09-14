<?php

namespace Tests\Feature\VisitorManagement;

use App\Modules\Companies\Models\Company;
use App\Modules\Properties\Models\Property;
use App\Modules\VisitorManagement\Models\Visitor;
use App\Modules\VisitorManagement\Repositories\EloquentVisitorRepository;
use App\Modules\VisitorManagement\Repositories\VisitorRepositoryInterface;
use App\Modules\VisitorManagement\Services\VisitorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class VisitorServiceTest extends TestCase
{
    use RefreshDatabase;

    protected VisitorService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(VisitorService::class);
    }

    protected function makeVisitor(Company $company, array $overrides = []): Visitor
    {
        $property = Property::factory()->create(['company_id' => $company->id]);

        return Visitor::factory()->create(array_merge([
            'company_id' => $company->id,
            'property_id' => $property->id,
        ], $overrides));
    }

    public function test_the_repository_interface_resolves_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentVisitorRepository::class, app(VisitorRepositoryInterface::class));
    }

    public function test_paginate_filters_by_search_property_status_and_purpose(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $this->makeVisitor($company, ['property_id' => $property->id, 'visitor_name' => 'John Delivery', 'purpose' => 'delivery']);
        $this->makeVisitor($company, ['visitor_name' => 'Jane Meeting', 'purpose' => 'meeting', 'check_out_at' => now()]);

        $this->assertSame(1, $this->service->paginate('Delivery', null, null, null)->total());
        $this->assertSame(1, $this->service->paginate(null, $property->id, null, null)->total());
        $this->assertSame(1, $this->service->paginate(null, null, 'checked_out', null)->total());
        $this->assertSame(1, $this->service->paginate(null, null, 'checked_in', null)->total());
        $this->assertSame(1, $this->service->paginate(null, null, null, 'meeting')->total());
        $this->assertSame(2, $this->service->paginate(null, null, null, null)->total());
    }

    public function test_create_defaults_check_in_at_to_now_when_omitted(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);

        $visitor = $this->service->create([
            'company_id' => $company->id,
            'property_id' => $property->id,
            'visitor_name' => 'Walk-In Guest',
            'purpose' => 'other',
        ]);

        $this->assertNotNull($visitor->check_in_at);
        $this->assertTrue($visitor->check_in_at->diffInSeconds(now()) < 5);
    }

    public function test_check_out_sets_check_out_at_and_the_checking_out_user(): void
    {
        $company = Company::factory()->create();
        $visitor = $this->makeVisitor($company);

        $updated = $this->service->checkOut($visitor, ['checked_out_by_id' => null]);

        $this->assertNotNull($updated->check_out_at);
        $this->assertFalse($updated->isCheckedIn());
    }

    public function test_check_out_rejects_a_visitor_who_already_checked_out(): void
    {
        $company = Company::factory()->create();
        $visitor = $this->makeVisitor($company, ['check_out_at' => now()]);

        $this->expectException(ValidationException::class);

        $this->service->checkOut($visitor, []);
    }
}
