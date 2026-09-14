<?php

namespace Tests\Feature\Documents;

use App\Modules\Companies\Models\Company;
use App\Modules\Documents\Models\Document;
use App\Modules\Documents\Repositories\DocumentRepositoryInterface;
use App\Modules\Documents\Repositories\EloquentDocumentRepository;
use App\Modules\Documents\Services\DocumentService;
use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DocumentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(DocumentService::class);
    }

    public function test_the_repository_interface_resolves_to_the_eloquent_implementation(): void
    {
        $this->assertInstanceOf(EloquentDocumentRepository::class, app(DocumentRepositoryInterface::class));
    }

    public function test_paginate_filters_by_search_category_documentable_type_and_expiring_soon(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);
        Document::factory()->create(['company_id' => $company->id, 'title' => 'Property Deed', 'category' => 'deed', 'documentable_type' => Property::class, 'documentable_id' => $property->id]);
        Document::factory()->create(['company_id' => $company->id, 'title' => 'Business License', 'category' => 'license', 'expiry_date' => now()->addDays(5)->toDateString()]);

        $this->assertSame(1, $this->service->paginate('Deed', null, null, false)->total());
        $this->assertSame(1, $this->service->paginate(null, 'license', null, false)->total());
        $this->assertSame(1, $this->service->paginate(null, null, Property::class, false)->total());
        $this->assertSame(1, $this->service->paginate(null, null, null, true)->total());
        $this->assertSame(2, $this->service->paginate(null, null, null, false)->total());
    }

    public function test_create_attaches_the_uploaded_file_to_the_correct_companys_disk(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $file = UploadedFile::fake()->create('lease.pdf', 100, 'application/pdf');

        $document = $this->service->create([
            'company_id' => $company->id,
            'title' => 'Signed Lease',
            'category' => 'lease_agreement',
            'file' => $file,
        ]);

        $this->assertNotNull($document->fileUrl());
        $this->assertSame('lease.pdf', $document->fileName());
    }

    public function test_create_without_a_file_still_creates_the_row(): void
    {
        $company = Company::factory()->create();

        $document = $this->service->create([
            'company_id' => $company->id,
            'title' => 'Standalone Note',
            'category' => 'other',
        ]);

        $this->assertNull($document->fileUrl());
    }

    public function test_update_without_a_new_file_keeps_the_existing_one(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $document = $this->service->create([
            'company_id' => $company->id,
            'title' => 'Original',
            'category' => 'other',
            'file' => UploadedFile::fake()->create('original.pdf', 50, 'application/pdf'),
        ]);

        $updated = $this->service->update($document, ['title' => 'Renamed', 'category' => 'other']);

        $this->assertSame('Renamed', $updated->title);
        $this->assertSame('original.pdf', $updated->fileName());
    }

    public function test_update_with_a_new_file_replaces_the_old_one(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $document = $this->service->create([
            'company_id' => $company->id,
            'title' => 'Original',
            'category' => 'other',
            'file' => UploadedFile::fake()->create('original.pdf', 50, 'application/pdf'),
        ]);

        $updated = $this->service->update($document, [
            'title' => 'Original',
            'category' => 'other',
            'file' => UploadedFile::fake()->create('replacement.pdf', 50, 'application/pdf'),
        ]);

        $this->assertSame('replacement.pdf', $updated->fileName());
        $this->assertCount(1, $updated->getMedia('file'));
    }
}
