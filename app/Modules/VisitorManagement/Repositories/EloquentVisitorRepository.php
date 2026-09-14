<?php

namespace App\Modules\VisitorManagement\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\VisitorManagement\Models\Visitor;

class EloquentVisitorRepository extends BaseRepository implements VisitorRepositoryInterface
{
    public function __construct(Visitor $model)
    {
        parent::__construct($model);
    }
}
