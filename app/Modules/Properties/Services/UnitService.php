<?php

namespace App\Modules\Properties\Services;

use App\Core\Services\BaseService;
use App\Modules\Properties\Models\Building;
use App\Modules\Properties\Repositories\UnitRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

/**
 * @property UnitRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class UnitService extends BaseService
{
    public function __construct(UnitRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function forBuilding(Building $building): Collection
    {
        return $building->units()->latest()->get();
    }
}
