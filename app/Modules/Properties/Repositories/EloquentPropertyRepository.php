<?php

namespace App\Modules\Properties\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Properties\Models\Property;

class EloquentPropertyRepository extends BaseRepository implements PropertyRepositoryInterface
{
    public function __construct(Property $model)
    {
        parent::__construct($model);
    }
}
