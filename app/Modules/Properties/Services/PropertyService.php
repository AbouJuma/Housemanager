<?php

namespace App\Modules\Properties\Services;

use App\Core\Services\BaseService;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Repositories\PropertyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * @property PropertyRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class PropertyService extends BaseService
{
    public function __construct(PropertyRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function paginate(?string $search, ?string $type, ?string $status, int $perPage = 15, ?string $region = null, ?string $district = null): LengthAwarePaginator
    {
        return $this->repository->query()
            ->with('landlord:id,uuid,name')
            ->withCount(['buildings', 'units'])
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('city', 'like', "%{$search}%")
                    ->orWhere('state', 'like', "%{$search}%")
                    ->orWhere('area', 'like', "%{$search}%");
            }))
            // Region is stored in `state`, district in `city` (config/tanzania.php).
            ->when($region, fn ($query) => $query->where('state', $region))
            ->when($district, fn ($query) => $query->where('city', $district))
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }
}
