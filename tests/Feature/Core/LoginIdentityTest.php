<?php

namespace Tests\Feature\Core;

use App\Core\Auth\LoginIdentity;
use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Services\TenantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class LoginIdentityTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(Company $company): User
    {
        $admin = User::factory()->create(['company_id' => $company->id]);
        PermissionsTeam::runAs($company->id, function () use ($admin): void {
            $role = Role::findOrCreate('Admin', 'web');
            $role->syncPermissions(['tenants.viewAny', 'tenants.view', 'tenants.create', 'tenants.update', 'users.viewAny', 'users.view', 'users.create']);
            $admin->assignRole($role);
        });

        return $admin;
    }

    public function test_a_new_tenant_can_sign_in_to_the_portal_straight_away_by_phone_or_username(): void
    {
        Notification::fake();
        $company = Company::factory()->create();

        $this->actingAs($this->admin($company))->post(route('tenants.store'), [
            'name' => 'Amina Mushi', 'type' => 'individual', 'phone' => '0712 345 678',
        ])->assertRedirect()->assertSessionHas('success', fn ($message) => str_contains($message, 'username: amina.mushi') && str_contains($message, 'password: 0712345678'));

        auth()->logout();

        // By phone, in another format, and the password typed as +255...
        $this->post(route('portal.login'), ['email' => '+255712345678', 'password' => '+255 712 345 678'])
            ->assertRedirect(route('portal.dashboard'));
        $this->assertAuthenticated('tenant');

        auth('tenant')->logout();

        $this->post(route('portal.login'), ['email' => 'Amina.Mushi', 'password' => '0712345678'])
            ->assertRedirect(route('portal.dashboard'));
    }

    public function test_wrong_password_is_rejected_with_a_readable_error(): void
    {
        Notification::fake();
        $company = Company::factory()->create();
        app(TenantService::class)->create(['company_id' => $company->id, 'name' => 'Juma', 'type' => 'individual', 'phone' => '0754000111']);

        $this->from(route('portal.login'))->post(route('portal.login'), ['email' => '0754000111', 'password' => 'nope'])
            ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
        $this->assertGuest('tenant');
    }

    public function test_a_staff_member_without_an_email_signs_in_with_their_phone(): void
    {
        Notification::fake();
        $company = Company::factory()->create();

        $this->actingAs($this->admin($company))->post(route('users.store'), [
            'name' => 'Neema Staff', 'phone' => '0688 222 333',
        ])->assertRedirect()->assertSessionHasNoErrors();

        auth()->logout();

        $this->post(route('login'), ['email' => '0688222333', 'password' => '0688222333'])->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }

    public function test_a_taken_username_is_refused_on_the_form(): void
    {
        Notification::fake();
        $company = Company::factory()->create();
        Tenant::factory()->create(['company_id' => $company->id, 'username' => 'amina']);

        $this->actingAs($this->admin($company))
            ->post(route('tenants.store'), ['name' => 'Another Amina', 'type' => 'individual', 'username' => 'AMINA'])
            ->assertSessionHasErrors(['username' => 'The username "AMINA" is already taken - try another one.']);

        $this->actingAs($this->admin($company))
            ->post(route('tenants.store'), ['name' => 'Spaced', 'type' => 'individual', 'username' => 'has space'])
            ->assertSessionHasErrors('username');
    }

    public function test_the_live_check_reports_availability_and_suggests_one(): void
    {
        $company = Company::factory()->create();
        Tenant::factory()->create(['company_id' => $company->id, 'username' => 'amina.mushi']);
        $admin = $this->admin($company);

        $this->actingAs($admin)->getJson(route('username.check', ['type' => 'tenant', 'username' => 'amina.mushi']))
            ->assertOk()->assertJson(['available' => false, 'suggestion' => 'amina.mushi2']);

        $this->actingAs($admin)->getJson(route('username.check', ['type' => 'tenant', 'username' => 'brand.new']))
            ->assertOk()->assertJson(['available' => true]);

        $this->actingAs($admin)->getJson(route('username.check', ['type' => 'tenant', 'name' => 'Amina Mushi']))
            ->assertOk()->assertJson(['available' => null, 'suggestion' => 'amina.mushi2']);
    }

    public function test_usernames_are_generated_unique_from_the_name(): void
    {
        $company = Company::factory()->create();
        $first = Tenant::factory()->create(['company_id' => $company->id, 'name' => 'Zawadi Ali', 'username' => null]);
        $second = Tenant::factory()->create(['company_id' => $company->id, 'name' => 'Zawadi Ali', 'username' => null]);

        $this->assertSame('zawadi.ali', $first->username);
        $this->assertSame('zawadi.ali2', $second->username);
        // A phone typed as the password in any common format still matches.
        $variants = LoginIdentity::passwordVariants('+255 712 345 678');
        $this->assertContains('0712345678', $variants);
        $this->assertContains('255712345678', $variants);
        $this->assertSame(['not a phone'], LoginIdentity::passwordVariants('not a phone'));
    }
}
