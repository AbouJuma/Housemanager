<?php

namespace App\Modules\Landlords\Services;

use App\Core\Auth\AccountProvisioner;
use App\Core\Services\BaseService;
use App\Modules\Landlords\Notifications\LandlordWelcome;
use App\Modules\Landlords\Repositories\LandlordRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * @property LandlordRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class LandlordService extends BaseService
{
    protected const BANK_DETAIL_FIELDS = ['bank_name', 'bank_account_name', 'bank_account_number', 'bank_branch', 'bank_swift_code'];

    public function __construct(LandlordRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function paginate(?string $search, ?string $type, ?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            ->withCount('properties')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    protected ?string $issuedPassword = null;

    public function create(array $attributes): Model
    {
        $landlord = parent::create($this->withAssembledBankDetails($attributes));

        // Straight into the portal/app: username + phone-number password.
        $this->issuedPassword = app(AccountProvisioner::class)->grantAccess($landlord);

        $landlord->notify(new LandlordWelcome($this->welcomeData($landlord, $this->issuedPassword)));

        return $landlord;
    }

    public function update(Model $model, array $attributes): Model
    {
        return parent::update($model, $this->withAssembledBankDetails($attributes));
    }

    /**
     * @return array<string, mixed> placeholders for the landlord_welcome template
     */
    protected function welcomeData(Model $landlord, string $password): array
    {
        $company = $landlord->company;
        $propertyNames = $landlord->properties->pluck('name')->implode(', ') ?: 'No properties yet';

        return [
            'landlord_name' => $landlord->name,
            'company_name' => $company->name,
            'property_names' => $propertyNames,
            ...app(AccountProvisioner::class)->credentials($landlord, $password, AccountProvisioner::loginUrlFor('landlord')),
        ];
    }

    /**
     * The plain password the last create() issued - shown once to staff
     * so they can pass it on, never stored.
     */
    public function issuedPassword(): ?string
    {
        return $this->issuedPassword;
    }

    /**
     * Assembles the form/API's individual bank_* fields into the single
     * `bank_details` array the model's encrypted:array cast expects,
     * mirroring how Module 7's Integration credential fields get merged
     * before saving. Lives here, not in the controllers, since both the
     * web and API controllers need identical behavior - this is
     * business logic, not an HTTP concern.
     */
    protected function withAssembledBankDetails(array $attributes): array
    {
        $bankDetails = array_filter(
            array_combine(
                array_map(fn ($key) => str_replace('bank_', '', $key), self::BANK_DETAIL_FIELDS),
                array_map(fn ($key) => $attributes[$key] ?? null, self::BANK_DETAIL_FIELDS),
            ),
            fn ($value) => $value !== null && $value !== '',
        );

        $attributes['bank_details'] = $bankDetails !== [] ? $bankDetails : null;

        return array_diff_key($attributes, array_flip(self::BANK_DETAIL_FIELDS));
    }
}
