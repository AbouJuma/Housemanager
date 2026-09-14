<?php

namespace Tests\Feature\Core;

use App\Core\Http\Middleware\IdentifyCompany;
use App\Core\Tenancy\CompanyContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class IdentifyCompanyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware(['web', IdentifyCompany::class])
            ->get('/__test/identify-company', fn () => response()->json([
                'company_id' => app(CompanyContext::class)->get(),
            ]));
    }

    public function test_it_sets_the_company_context_from_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $user->company_id = 7;

        $response = $this->actingAs($user)->getJson('/__test/identify-company');

        $response->assertOk()->assertJson(['company_id' => 7]);
    }

    public function test_it_leaves_the_company_context_empty_for_guests(): void
    {
        $response = $this->getJson('/__test/identify-company');

        $response->assertOk()->assertJson(['company_id' => null]);
    }
}
