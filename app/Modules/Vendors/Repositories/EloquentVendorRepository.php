<?php

namespace App\Modules\Vendors\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Vendors\Models\Vendor;

class EloquentVendorRepository extends BaseRepository implements VendorRepositoryInterface
{
    public function __construct(Vendor $model)
    {
        parent::__construct($model);
    }
}
