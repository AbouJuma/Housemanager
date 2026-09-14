<?php

namespace App\Modules\Vendors\Services;

use App\Core\Services\BaseService;
use App\Modules\Vendors\Repositories\VendorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

/**
 * @property VendorRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class VendorService extends BaseService
{
    protected const BANK_DETAIL_FIELDS = ['bank_name', 'bank_account_name', 'bank_account_number', 'bank_branch', 'bank_swift_code'];

    public function __construct(VendorRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function paginate(?string $search, ?string $category, ?string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            ->withCount('expenses')
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            }))
            ->when($category, fn ($query) => $query->where('category', $category))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): Model
    {
        return parent::create($this->withAssembledBankDetails($attributes));
    }

    public function update(Model $model, array $attributes): Model
    {
        return parent::update($model, $this->withAssembledBankDetails($attributes));
    }

    /**
     * Same assembly pattern as LandlordService (ARCHITECTURE.md §10b) -
     * lives here, not in either controller, so web and API don't
     * duplicate it.
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
