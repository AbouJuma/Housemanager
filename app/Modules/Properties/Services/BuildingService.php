<?php

namespace App\Modules\Properties\Services;

use App\Core\Services\BaseService;
use App\Modules\Properties\Models\Property;
use App\Modules\Properties\Repositories\BuildingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property BuildingRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class BuildingService extends BaseService
{
    public function __construct(BuildingRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function forProperty(Property $property): Collection
    {
        return $property->buildings()->withCount('units')->latest()->get();
    }
}
