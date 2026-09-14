<?php

namespace App\Modules\Landlords\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Landlords\Models\Landlord;

class EloquentLandlordRepository extends BaseRepository implements LandlordRepositoryInterface
{
    public function __construct(Landlord $model)
    {
        parent::__construct($model);
    }
}
