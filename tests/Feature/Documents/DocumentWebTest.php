<?php

namespace Tests\Feature\Documents;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Documents\Models\Document;
use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentWebTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['documents.viewAny', 'documents.view', 'documents.create', 'documents.update', 'documents.delete']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_saas_owner_cannot_access_documents(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('documents.index'))->assertForbidden();
    }

    public function test_a_company_admin_can_upload_a_standalone_document(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $response = $this->actingAs($admin)->post(route('documents.store'), [
            'title' => 'Business License',
            'category' => 'license',
            'file' => UploadedFile::fake()->create('license.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', ['company_id' => $company->id, 'title' => 'Business License', 'documentable_type' => null]);
    }

    public function test_a_company_admin_can_upload_a_document_attached_to_a_property(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $property = Property::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->post(route('documents.store'), [
            'documentable_type' => 'property',
            'documentable_id' => $property->id,
            'title' => 'Property Deed',
            'category' => 'deed',
            'file' => UploadedFile::fake()->create('deed.pdf', 100, 'application/pdf'),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('documents', [
            'company_id' => $company->id,
            'documentable_type' => Property::class,
            'documentable_id' => $property->id,
        ]);
        $this->assertCount(1, $property->fresh()->documents);
    }

    public function test_a_document_cannot_be_attached_to_another_companys_property(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherProperty = Property::factory()->create(['company_id' => $otherCompany->id]);

        $response = $this->actingAs($admin)->post(route('documents.store'), [
            'documentable_type' => 'property',
            'documentable_id' => $otherProperty->id,
            'title' => 'Property Deed',
            'category' => 'deed',
            'file' => UploadedFile::fake()->create('deed.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors('documentable_id');
    }

    public function test_a_company_admin_cannot_view_another_companys_document(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $otherDocument = Document::factory()->create(['company_id' => $otherCompany->id]);

        $this->actingAs($admin)->get(route('documents.show', $otherDocument))->assertForbidden();
    }

    public function test_updating_a_document_without_a_new_file_keeps_the_existing_one(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $document = app(\App\Modules\Documents\Services\DocumentService::class)->create([
            'company_id' => $company->id,
            'title' => 'Original',
            'category' => 'other',
            'file' => UploadedFile::fake()->create('original.pdf', 50, 'application/pdf'),
        ]);

        $response = $this->actingAs($admin)->put(route('documents.update', $document), [
            'title' => 'Renamed',
            'category' => 'other',
        ]);

        $response->assertRedirect();
        $this->assertSame('Renamed', $document->fresh()->title);
        $this->assertSame('original.pdf', $document->fresh()->fileName());
    }

    public function test_deleting_a_document_redirects_to_the_index(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        $document = Document::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($admin)->delete(route('documents.destroy', $document));

        $response->assertRedirect(route('documents.index'));
        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }
}
