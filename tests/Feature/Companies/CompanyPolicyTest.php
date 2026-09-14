<?php

namespace Tests\Feature\Companies;

use App\Models\User;
use App\Modules\Companies\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_saas_owner_can_manage_any_company(): void
    {
        $owner = User::factory()->create();
        $company = Company::factory()->create();

        $this->assertTrue($owner->can('viewAny', Company::class));
        $this->assertTrue($owner->can('view', $company));
        $this->assertTrue($owner->can('create', Company::class));
        $this->assertTrue($owner->can('update', $company));
        $this->assertTrue($owner->can('delete', $company));
    }

    public function test_company_user_can_only_view_and_update_their_own_company(): void
    {
        $ownCompany = Company::factory()->create();
        $otherCompany = Company::factory()->create();

        $user = User::factory()->create();
        $user->company_id = $ownCompany->id;

        $this->assertFalse($user->can('viewAny', Company::class));
        $this->assertTrue($user->can('view', $ownCompany));
        $this->assertFalse($user->can('view', $otherCompany));
        $this->assertFalse($user->can('create', Company::class));
        $this->assertTrue($user->can('update', $ownCompany));
        $this->assertFalse($user->can('update', $otherCompany));
        $this->assertFalse($user->can('delete', $ownCompany));
    }
}
