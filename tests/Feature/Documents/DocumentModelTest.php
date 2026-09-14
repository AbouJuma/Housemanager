<?php

namespace Tests\Feature\Documents;

use App\Modules\Companies\Models\Company;
use App\Modules\Documents\Models\Document;
use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DocumentModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $company = Company::factory()->create();
        $document = Document::factory()->create(['company_id' => $company->id]);

        $this->assertTrue(Str::isUuid($document->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new Document())->getRouteKeyName());
    }

    public function test_is_expired_and_is_expiring_soon_reflect_the_expiry_date(): void
    {
        $company = Company::factory()->create();
        $expired = Document::factory()->create(['company_id' => $company->id, 'expiry_date' => now()->subDay()->toDateString()]);
        $expiringSoon = Document::factory()->create(['company_id' => $company->id, 'expiry_date' => now()->addDays(10)->toDateString()]);
        $farOut = Document::factory()->create(['company_id' => $company->id, 'expiry_date' => now()->addYear()->toDateString()]);
        $noExpiry = Document::factory()->create(['company_id' => $company->id, 'expiry_date' => null]);

        $this->assertTrue($expired->isExpired());
        $this->assertFalse($expired->isExpiringSoon());

        $this->assertFalse($expiringSoon->isExpired());
        $this->assertTrue($expiringSoon->isExpiringSoon());

        $this->assertFalse($farOut->isExpiringSoon());
        $this->assertFalse($noExpiry->isExpired());
        $this->assertFalse($noExpiry->isExpiringSoon());
    }

    public function test_documentable_type_key_reverses_the_stored_fqcn_back_to_its_short_form(): void
    {
        $company = Company::factory()->create();
        $property = Property::factory()->create(['company_id' => $company->id]);
        $document = Document::factory()->create([
            'company_id' => $company->id,
            'documentable_type' => Property::class,
            'documentable_id' => $property->id,
        ]);

        $this->assertSame('property', $document->documentableTypeKey());
    }

    public function test_documentable_type_key_is_null_for_a_standalone_document(): void
    {
        $company = Company::factory()->create();
        $document = Document::factory()->create(['company_id' => $company->id]);

        $this->assertNull($document->documentableTypeKey());
    }
}
