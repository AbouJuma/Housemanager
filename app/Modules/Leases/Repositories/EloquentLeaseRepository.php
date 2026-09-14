<?php

namespace App\Modules\Leases\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Leases\Models\Lease;

class EloquentLeaseRepository extends BaseRepository implements LeaseRepositoryInterface
{
    public function __construct(Lease $model)
    {
        parent::__construct($model);
    }
}
