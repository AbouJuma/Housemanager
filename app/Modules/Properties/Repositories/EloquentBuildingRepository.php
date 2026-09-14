<?php

namespace App\Modules\Properties\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Properties\Models\Building;

class EloquentBuildingRepository extends BaseRepository implements BuildingRepositoryInterface
{
    public function __construct(Building $model)
    {
        parent::__construct($model);
    }
}
