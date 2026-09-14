<?php

namespace Tests\Feature\Vendors;

use App\Modules\Companies\Models\Company;
use App\Modules\Expenses\Models\Expense;
use App\Modules\Accounting\Models\Account;
use App\Modules\Vendors\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class VendorModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_uuid_is_generated_on_creation(): void
    {
        $vendor = Vendor::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertTrue(Str::isUuid($vendor->uuid));
    }

    public function test_the_route_key_name_is_uuid(): void
    {
        $this->assertSame('uuid', (new Vendor())->getRouteKeyName());
    }

    public function test_bank_details_round_trip_encrypted(): void
    {
        $vendor = Vendor::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'bank_details' => ['name' => 'CRDB Bank', 'account_number' => '12345'],
        ]);

        $this->assertSame('CRDB Bank', $vendor->fresh()->bankDetail('name'));
    }

    public function test_bank_details_are_stored_encrypted_at_rest(): void
    {
        $vendor = Vendor::factory()->create([
            'company_id' => Company::factory()->create()->id,
            'bank_details' => ['account_number' => 'super-secret-account-number'],
        ]);

        $raw = DB::table('vendors')->where('id', $vendor->id)->value('bank_details');

        $this->assertStringNotContainsString('super-secret-account-number', $raw);
    }

    public function test_photo_url_is_null_without_media(): void
    {
        $vendor = Vendor::factory()->create(['company_id' => Company::factory()->create()->id]);

        $this->assertNull($vendor->photoUrl());
    }

    public function test_it_has_many_expenses(): void
    {
        $company = Company::factory()->create();
        $vendor = Vendor::factory()->create(['company_id' => $company->id]);
        $account = Account::factory()->create(['company_id' => $company->id, 'type' => 'expense']);
        Expense::factory()->create(['company_id' => $company->id, 'account_id' => $account->id, 'vendor_id' => $vendor->id]);

        $this->assertCount(1, $vendor->expenses);
    }
}
