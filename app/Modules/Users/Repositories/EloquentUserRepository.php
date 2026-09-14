<?php

namespace App\Modules\Users\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Models\User;

class EloquentUserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }
}
