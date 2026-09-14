<?php

namespace App\Modules\Properties\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Properties\Models\Unit;

class EloquentUnitRepository extends BaseRepository implements UnitRepositoryInterface
{
    public function __construct(Unit $model)
    {
        parent::__construct($model);
    }
}
