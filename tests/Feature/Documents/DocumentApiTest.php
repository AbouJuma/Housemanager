<?php

namespace Tests\Feature\Documents;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Documents\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentApiTest extends TestCase
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

    public function test_a_company_admin_can_list_their_own_documents_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        Document::factory()->create(['company_id' => $company->id]);

        $this->getJson('/api/v1/documents')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_a_company_admin_can_upload_a_document_via_api(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);

        $response = $this->post('/api/v1/documents', [
            'title' => 'Business License',
            'category' => 'license',
            'file' => UploadedFile::fake()->create('license.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()->assertJsonPath('data.title', 'Business License');
    }

    public function test_a_company_admin_cannot_view_another_companys_document_via_api(): void
    {
        $company = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $otherDocument = Document::factory()->create(['company_id' => $otherCompany->id]);

        $this->getJson("/api/v1/documents/{$otherDocument->uuid}")->assertForbidden();
    }

    public function test_a_company_admin_can_delete_their_own_document_via_api(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);
        Sanctum::actingAs($admin);
        $document = Document::factory()->create(['company_id' => $company->id]);

        $this->deleteJson("/api/v1/documents/{$document->uuid}")->assertNoContent();

        $this->assertSoftDeleted('documents', ['id' => $document->id]);
    }
}
