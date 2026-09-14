<?php

namespace Tests\Feature\Landlords;

use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Properties\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LandlordModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $landlord = Landlord::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertTrue(Str::isUuid($landlord->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new Landlord())->getRouteKeyName());
    }

    public function test_properties_relation(): void
    {
        $company = Company::factory()->create();
        $landlord = Landlord::factory()->create(['company_id' => $company->id]);
        Property::factory()->count(2)->create(['company_id' => $company->id, 'landlord_id' => $landlord->id]);
        Property::factory()->create(['company_id' => $company->id]); // unassigned

        $this->assertCount(2, $landlord->properties);
    }

    public function test_bank_details_round_trips_encrypted_and_readable_via_bank_detail(): void
    {
        $landlord = Landlord::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'bank_details' => ['name' => 'CRDB Bank', 'account_number' => '1234567890'],
        ]);

        $this->assertSame('CRDB Bank', $landlord->fresh()->bankDetail('name'));
        $this->assertSame('1234567890', $landlord->fresh()->bankDetail('account_number'));
        $this->assertNull($landlord->fresh()->bankDetail('swift_code'));
    }

    public function test_bank_details_are_stored_encrypted_at_rest(): void
    {
        $landlord = Landlord::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'bank_details' => ['account_number' => 'super-secret-account-number'],
        ]);

        $raw = \Illuminate\Support\Facades\DB::table('landlords')->where('id', $landlord->id)->value('bank_details');

        $this->assertStringNotContainsString('super-secret-account-number', $raw);
    }

    public function test_photo_url_is_null_without_media(): void
    {
        $landlord = Landlord::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertNull($landlord->photoUrl());
    }
}
