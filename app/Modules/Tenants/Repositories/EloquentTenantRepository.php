<?php

namespace App\Modules\Tenants\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Tenants\Models\Tenant;

class EloquentTenantRepository extends BaseRepository implements TenantRepositoryInterface
{
    public function __construct(Tenant $model)
    {
        parent::__construct($model);
    }
}
