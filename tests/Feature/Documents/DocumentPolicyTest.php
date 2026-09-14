<?php

namespace Tests\Feature\Documents;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Documents\Models\Document;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DocumentPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompanyUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $permissions): void {
            $role = Role::findOrCreate('Document Manager', 'web');
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        });

        return $user;
    }

    public function test_saas_owner_cannot_manage_documents(): void
    {
        $owner = User::factory()->create();
        $document = Document::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertFalse($owner->can('viewAny', Document::class));
        $this->assertFalse($owner->can('view', $document));
        $this->assertFalse($owner->can('create', Document::class));
    }

    public function test_a_company_user_with_permissions_can_manage_their_own_companys_documents(): void
    {
        $company = Company::factory()->create();
        $user = $this->makeCompanyUser($company, ['documents.viewAny', 'documents.view', 'documents.create', 'documents.update', 'documents.delete']);
        $document = Document::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $document): void {
            $this->assertTrue($user->can('viewAny', Document::class));
            $this->assertTrue($user->can('view', $document));
            $this->assertTrue($user->can('create', Document::class));
            $this->assertTrue($user->can('update', $document));
            $this->assertTrue($user->can('delete', $document));
        });
    }

    public function test_permissions_do_not_cross_the_company_boundary(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = $this->makeCompanyUser($ownCompany, ['documents.view', 'documents.update', 'documents.delete']);
        $otherDocument = Document::factory()->create(['company_id' => $otherCompany->id]);

        PermissionsTeam::runAs($ownCompany->id, function () use ($user, $otherDocument): void {
            $this->assertFalse($user->can('view', $otherDocument));
            $this->assertFalse($user->can('update', $otherDocument));
            $this->assertFalse($user->can('delete', $otherDocument));
        });
    }
}
