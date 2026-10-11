<?php

namespace Tests\Feature\Companies;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Integrations\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_can_list_companies(): void
    {
        $owner = User::factory()->create();
        Company::factory()->count(3)->create();

        $this->actingAs($owner)->get(route('companies.index'))->assertOk();
    }

    public function test_a_company_user_cannot_list_all_companies(): void
    {
        $ownCompany = Company::factory()->create();
        $user = User::factory()->create();
        $user->company_id = $ownCompany->id;

        $this->actingAs($user)->get(route('companies.index'))->assertForbidden();
    }

    public function test_saas_owner_can_create_a_company(): void
    {
        $owner = User::factory()->create();

        $response = $this->actingAs($owner)->post(route('companies.store'), [
            'name' => 'Acme Property Group',
        ]);

        $this->assertDatabaseHas('companies', [
            'name' => 'Acme Property Group',
            'slug' => 'acme-property-group',
        ]);

        $company = Company::where('slug', 'acme-property-group')->firstOrFail();
        $response->assertRedirect(route('companies.show', $company));
    }

    public function test_a_company_user_cannot_create_a_company(): void
    {
        $ownCompany = Company::factory()->create();
        $user = User::factory()->create();
        $user->company_id = $ownCompany->id;

        $response = $this->actingAs($user)->post(route('companies.store'), ['name' => 'Blocked Co']);

        $response->assertForbidden();
        $this->assertDatabaseMissing('companies', ['name' => 'Blocked Co']);
    }

    public function test_a_company_user_can_view_and_update_their_own_company(): void
    {
        $company = Company::factory()->create(['name' => 'Original Name']);
        $user = User::factory()->create();
        $user->company_id = $company->id;

        $this->actingAs($user)->get(route('companies.show', $company))->assertOk();

        $response = $this->actingAs($user)->put(route('companies.update', $company), [
            'name' => 'Updated Name',
        ]);

        $response->assertRedirect(route('companies.show', $company));
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'name' => 'Updated Name']);
    }

    public function test_a_company_user_cannot_view_another_companys_page(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();
        $user = User::factory()->create();
        $user->company_id = $ownCompany->id;

        $this->actingAs($user)->get(route('companies.show', $otherCompany))->assertForbidden();
    }

    public function test_only_the_saas_owner_can_delete_a_company(): void
    {
        $company = Company::factory()->create();
        $owner = User::factory()->create();

        $response = $this->actingAs($owner)->delete(route('companies.destroy', $company));

        $response->assertRedirect(route('companies.index'));
        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_branding_can_be_updated_with_a_logo(): void
    {
        Storage::fake('public');
        $company = Company::factory()->create();
        $owner = User::factory()->create();
        $logo = UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($owner)->put(route('companies.branding.update', $company), [
            'logo' => $logo,
            'primary_color' => '#111111',
        ]);

        $response->assertRedirect(route('companies.show', $company));
        $this->assertSame('#111111', $company->fresh()->primary_color);
        $this->assertNotNull($company->fresh()->logoUrl());
    }

    public function test_branding_upload_fails_gracefully_for_an_unconfigured_storage_provider(): void
    {
        $company = Company::factory()->create();
        Integration::create([
            'company_id' => $company->id,
            'type' => 'storage',
            'provider' => 'gcs',
            'is_active' => true,
        ]);
        $owner = User::factory()->create();
        $logo = UploadedFile::fake()->image('logo.png');

        $response = $this->actingAs($owner)->put(route('companies.branding.update', $company), [
            'logo' => $logo,
        ]);

        $response->assertRedirect(route('companies.show', $company));
        $response->assertSessionHas('error');
        $this->assertNull($company->fresh()->logoUrl());
    }
}
