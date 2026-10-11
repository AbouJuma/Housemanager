<?php

namespace Tests\Feature\Settings;

use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\Settings\Services\PaymentDetails;
use App\Modules\Settings\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Which bank account a tenant is told to pay into: the company decides
 * (Settings > payment_details_source) between its own account and each
 * landlord's own account.
 */
class PaymentDetailsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCompany(bool $withCompanyBank = true): Company
    {
        $company = Company::factory()->create(['name' => 'Acme Estates', 'bank_name' => null, 'account_number' => null]);

        if ($withCompanyBank) {
            $settings = app(SettingsService::class);
            $settings->setForCompany($company->id, 'company_bank_name', 'CRDB Bank');
            $settings->setForCompany($company->id, 'company_bank_account_name', 'Acme Estates Ltd');
            $settings->setForCompany($company->id, 'company_bank_account_number', '0150111222');
        }

        return $company;
    }

    protected function makeLandlord(Company $company, bool $withBank = true): Landlord
    {
        $factory = Landlord::factory();

        return ($withBank ? $factory->withBankDetails() : $factory)->create([
            'company_id' => $company->id,
            'name' => 'Juma Landlord',
            'bank_details' => $withBank ? ['name' => 'NMB Bank', 'account_name' => 'Juma Said', 'account_number' => '2233445566'] : null,
        ]);
    }

    protected function choose(Company $company, string $source): void
    {
        app(SettingsService::class)->setForCompany($company->id, 'payment_details_source', $source);
    }

    public function test_landlord_source_uses_the_landlords_own_account(): void
    {
        $company = $this->makeCompany();
        $landlord = $this->makeLandlord($company);
        $this->choose($company, 'landlord');

        $this->assertSame(
            ['bank_name' => 'NMB Bank', 'account_name' => 'Juma Said', 'account_number' => '2233445566'],
            app(PaymentDetails::class)->resolve($company, $landlord),
        );
    }

    public function test_company_source_uses_the_company_account_even_when_the_landlord_has_one(): void
    {
        $company = $this->makeCompany();
        $landlord = $this->makeLandlord($company);
        $this->choose($company, 'company');

        $this->assertSame(
            ['bank_name' => 'CRDB Bank', 'account_name' => 'Acme Estates Ltd', 'account_number' => '0150111222'],
            app(PaymentDetails::class)->resolve($company, $landlord),
        );
    }

    public function test_it_falls_back_to_the_other_account_when_the_chosen_one_is_empty(): void
    {
        $company = $this->makeCompany();
        $landlord = $this->makeLandlord($company, withBank: false);
        $this->choose($company, 'landlord');

        $this->assertSame('0150111222', app(PaymentDetails::class)->resolve($company, $landlord)['account_number']);

        $this->choose($company, 'company');
        $bareCompany = $this->makeCompany(withCompanyBank: false);
        $this->choose($bareCompany, 'company');
        $landlord2 = $this->makeLandlord($bareCompany);

        $this->assertSame('2233445566', app(PaymentDetails::class)->resolve($bareCompany, $landlord2)['account_number']);
    }

    public function test_the_account_name_defaults_sensibly_and_missing_values_are_a_dash(): void
    {
        $company = $this->makeCompany(withCompanyBank: false);
        $this->choose($company, 'company');

        $this->assertSame(
            ['bank_name' => '-', 'account_name' => 'Acme Estates', 'account_number' => '-'],
            app(PaymentDetails::class)->resolve($company, null),
        );
    }

    public function test_the_legacy_company_bank_columns_still_work_when_no_setting_is_saved(): void
    {
        $company = Company::factory()->create(['bank_name' => 'NBC Bank', 'account_number' => '9988776655']);
        $this->choose($company, 'company');

        $resolved = app(PaymentDetails::class)->resolve($company, null);

        $this->assertSame('NBC Bank', $resolved['bank_name']);
        $this->assertSame('9988776655', $resolved['account_number']);
    }
}
