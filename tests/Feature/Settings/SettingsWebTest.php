<?php

namespace Tests\Feature\Settings;

use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SettingsWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_can_view_and_update_system_settings(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('settings.system.edit'))->assertOk();

        $response = $this->actingAs($owner)->put(route('settings.system.update'), [
            'default_trial_days' => 30,
            'default_currency_code' => 'TZS',
        ]);

        $response->assertRedirect(route('settings.system.edit'));
        $this->assertSame(30, app(SettingsService::class)->getSystem('default_trial_days'));
        $this->assertSame('TZS', app(SettingsService::class)->getSystem('default_currency_code'));
    }

    public function test_saas_owner_cannot_access_company_settings(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('settings.edit'))->assertForbidden();
    }

    public function test_a_company_admin_can_view_and_update_company_settings(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $this->actingAs($admin)->get(route('settings.edit'))->assertOk();

        $response = $this->actingAs($admin)->put(route('settings.update'), [
            'date_format' => 'd/m/Y',
        ]);

        $response->assertRedirect(route('settings.edit'));
        $this->assertSame('d/m/Y', app(SettingsService::class)->getForCompany($company->id, 'date_format'));
    }

    public function test_a_company_admin_can_choose_and_save_the_payment_account(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $this->actingAs($admin)->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Company bank account')
            ->assertSee('name="company_bank_account_name"', false);

        $this->actingAs($admin)->put(route('settings.update'), [
            'date_format' => 'M j, Y',
            'payment_details_source' => 'company',
            'company_bank_name' => 'CRDB Bank',
            'company_bank_account_name' => 'Acme Estates Ltd',
            'company_bank_account_number' => '0150111222',
        ])->assertRedirect(route('settings.edit'));

        $settings = app(SettingsService::class);
        $this->assertSame('company', $settings->getForCompany($company->id, 'payment_details_source'));
        $this->assertSame('Acme Estates Ltd', $settings->getForCompany($company->id, 'company_bank_account_name'));
        $this->assertSame('0150111222', $settings->getForCompany($company->id, 'company_bank_account_number'));
    }

    public function test_an_unknown_payment_source_is_rejected(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $this->actingAs($admin)->put(route('settings.update'), ['date_format' => 'M j, Y', 'payment_details_source' => 'cash'])
            ->assertSessionHasErrors('payment_details_source');
    }

    public function test_a_company_admin_cannot_access_system_settings(): void
    {
        $company = Company::factory()->create();
        $admin = $this->makeCompanyAdmin($company);

        $this->actingAs($admin)->get(route('settings.system.edit'))->assertForbidden();
    }

    public function test_a_company_user_without_permission_cannot_access_settings(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get(route('settings.edit'))->assertForbidden();
    }

    protected function makeCompanyAdmin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);

        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['settings.view', 'settings.update']);
            $admin->assignRole($role);
        });

        return $admin;
    }
}
