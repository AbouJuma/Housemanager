<?php

namespace App\Modules\Maintenance\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Maintenance\Models\MaintenanceRequest;

class EloquentMaintenanceRequestRepository extends BaseRepository implements MaintenanceRequestRepositoryInterface
{
    public function __construct(MaintenanceRequest $model)
    {
        parent::__construct($model);
    }
}
