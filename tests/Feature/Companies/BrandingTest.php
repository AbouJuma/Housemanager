<?php

namespace Tests\Feature\Companies;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Companies\Support\BrandTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(Company $company, array $permissions): User
    {
        $user = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($user, $permissions): void {
            $role = Role::findOrCreate('Role '.uniqid(), 'web');
            $role->syncPermissions($permissions);
            $user->assignRole($role);
        });

        return $user;
    }

    public function test_a_company_admin_can_set_their_brand_colors(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeUser($company, ['settings.view', 'settings.update']);

        $this->actingAs($admin)->get(route('branding.edit'))->assertOk()->assertSee('Live preview');

        $this->actingAs($admin)
            ->put(route('branding.update'), ['primary_color' => '#1D4ED8', 'secondary_color' => '#38BDF8'])
            ->assertRedirect(route('branding.edit'));

        $this->assertSame('#1D4ED8', $company->fresh()->primary_color);
        $this->assertSame('#38BDF8', $company->fresh()->secondary_color);
    }

    public function test_the_brand_colors_are_applied_to_the_layout(): void
    {
        $company = Company::factory()->create(['primary_color' => '#7E22CE', 'secondary_color' => '#F97316']);
        $admin = $this->makeUser($company, ['settings.view']);

        $this->actingAs($admin)->get(route('branding.edit'))
            ->assertOk()
            ->assertSee('--brand: #7E22CE;', false)
            ->assertSee('--accent: #F97316;', false);
    }

    public function test_reset_restores_the_default_palette(): void
    {
        $company = Company::factory()->create(['primary_color' => '#7E22CE', 'secondary_color' => '#F97316']);
        $admin = $this->makeUser($company, ['settings.view', 'settings.update']);

        $this->actingAs($admin)->put(route('branding.update'), ['reset' => 1])->assertRedirect(route('branding.edit'));

        $this->assertNull($company->fresh()->primary_color);
        $this->assertSame(BrandTheme::DEFAULT_PRIMARY, BrandTheme::forCompany($company->fresh())->primary);
    }

    public function test_staff_without_the_settings_permission_cannot_change_branding(): void
    {
        $company = Company::factory()->create();
        $staff = $this->makeUser($company, ['settings.view']);

        $this->actingAs($staff)->put(route('branding.update'), ['primary_color' => '#000000'])->assertForbidden();

        $this->assertNull($company->fresh()->primary_color);
    }

    public function test_invalid_colors_are_rejected(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeUser($company, ['settings.view', 'settings.update']);

        $this->actingAs($admin)
            ->put(route('branding.update'), ['primary_color' => 'red; } body { display:none'])
            ->assertSessionHasErrors('primary_color');
    }

    public function test_button_text_flips_to_dark_on_a_light_brand_color(): void
    {
        $this->assertSame('#FFFFFF', BrandTheme::contrastText('#0F766E'));
        $this->assertSame('#101828', BrandTheme::contrastText('#FDE047'));
    }
}
