<?php

namespace Tests\Feature\Subscriptions;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnsureCompanyIsActiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_suspended_companys_user_is_blocked(): void
    {
        $company = Company::factory()->create(['status' => 'suspended']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    public function test_a_cancelled_companys_user_is_blocked(): void
    {
        $company = Company::factory()->create(['status' => 'cancelled']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    }

    public function test_a_trialing_companys_user_is_not_blocked(): void
    {
        $company = Company::factory()->create(['status' => 'trial']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_an_active_companys_user_is_not_blocked(): void
    {
        $company = Company::factory()->create(['status' => 'active']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
    }

    public function test_the_saas_owner_is_never_blocked(): void
    {
        $owner = User::factory()->create();

        $this->actingAs($owner)->get(route('dashboard'))->assertOk();
    }

    public function test_a_suspended_companys_user_can_still_log_out(): void
    {
        $company = Company::factory()->create(['status' => 'suspended']);
        $user = User::factory()->create(['company_id' => $company->id]);

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect();
        $this->assertGuest();
    }
}
