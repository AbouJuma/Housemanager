<?php

namespace App\Modules\Tenants\Services;

use App\Core\Auth\AccountProvisioner;
use App\Core\Services\BaseService;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\Tenants\Notifications\TenantWelcome;
use App\Modules\Tenants\Repositories\TenantRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * @property TenantRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class TenantService extends BaseService
{
    public function __construct(TenantRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function paginate(?string $search, ?string $type, ?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
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
        // Generate control number if not provided
        if (empty($attributes['control_number'])) {
            $companyId = $attributes['company_id'] ?? auth()->user()->company_id;
            $attributes['control_number'] = $this->generateControlNumber($companyId);
        }

        $tenant = parent::create($attributes);

        // Straight into the portal/app: username + phone-number password.
        $this->issuedPassword = app(AccountProvisioner::class)->grantAccess($tenant);

        $tenant->notify(new TenantWelcome($this->welcomeData($tenant, $this->issuedPassword)));

        return $tenant;
    }

    protected function generateControlNumber(int $companyId): string
    {
        $maxAttempts = 100;
        $attempts = 0;

        do {
            // Generate random 10-digit number starting with 9
            $controlNumber = '9' . str_pad((string) rand(0, 999999999), 9, '0', STR_PAD_LEFT);

            // Check if this control number already exists (including soft-deleted)
            $exists = Tenant::withTrashed()
                ->where('control_number', $controlNumber)
                ->exists();

            $attempts++;
        } while ($exists && $attempts < $maxAttempts);

        return $controlNumber;
    }

    /**
     * @return array<string, mixed> placeholders for the tenant_welcome template
     */
    protected function welcomeData(Tenant $tenant, string $password): array
    {
        return [
            'tenant_name' => $tenant->name,
            'company_name' => $tenant->company->name,
            'phone' => $tenant->phone,
            'control_number' => $tenant->control_number,
            ...app(AccountProvisioner::class)->credentials($tenant, $password, AccountProvisioner::loginUrlFor('tenant')),
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
}
