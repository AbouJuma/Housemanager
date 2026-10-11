<?php

namespace App\Modules\Settings\Services;

use App\Modules\Companies\Models\Company;
use App\Modules\Landlords\Models\Landlord;

/**
 * The bank account a tenant is told to pay into (bank name, account
 * name, account number). Each company chooses in Settings whether that
 * is the company's own account or the landlord's own account
 * (`payment_details_source`). If the chosen account has no account
 * number saved yet, the other one is used so the message is not left
 * blank; a value neither has is returned as "-".
 *
 * Landlord bank details are stored by LandlordService under the keys
 * name / account_name / account_number (the form's bank_* field names
 * with the "bank_" prefix stripped) - reading `bank_name` here used to
 * silently find nothing, so a landlord's account never appeared.
 */
class PaymentDetails
{
    public function __construct(protected SettingsService $settings)
    {
    }

    /**
     * @return array{bank_name: string, account_name: string, account_number: string}
     */
    public function resolve(Company $company, ?Landlord $landlord): array
    {
        $company = $this->fromCompany($company);
        $landlord = $this->fromLandlord($landlord);

        $preferCompany = $this->settings->getForCompany($company['company_id'], 'payment_details_source') === 'company';

        [$primary, $secondary] = $preferCompany ? [$company, $landlord] : [$landlord, $company];

        $chosen = $primary['account_number'] !== null ? $primary : ($secondary['account_number'] !== null ? $secondary : $primary);

        return [
            'bank_name' => $chosen['bank_name'] ?: '-',
            'account_name' => $chosen['account_name'] ?: '-',
            'account_number' => $chosen['account_number'] ?: '-',
        ];
    }

    /**
     * @return array{company_id: int, bank_name: ?string, account_name: ?string, account_number: ?string}
     */
    protected function fromCompany(Company $company): array
    {
        $get = fn (string $key) => $this->settings->getForCompany($company->id, $key) ?: null;

        $accountNumber = $get('company_bank_account_number') ?? ($company->account_number ?: null);

        return [
            'company_id' => $company->id,
            'bank_name' => $get('company_bank_name') ?? ($company->bank_name ?: null),
            'account_name' => $get('company_bank_account_name') ?? $company->name,
            'account_number' => $accountNumber,
        ];
    }

    /**
     * @return array{bank_name: ?string, account_name: ?string, account_number: ?string}
     */
    protected function fromLandlord(?Landlord $landlord): array
    {
        $details = $landlord?->bank_details ?? [];
        $accountNumber = $details['account_number'] ?? null;

        return [
            'bank_name' => $details['name'] ?? null,
            'account_name' => $details['account_name'] ?? ($accountNumber ? $landlord?->name : null),
            'account_number' => $accountNumber ?: null,
        ];
    }
}
