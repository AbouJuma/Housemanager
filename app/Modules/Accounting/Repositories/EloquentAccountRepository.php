<?php

namespace App\Modules\Accounting\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Accounting\Models\Account;

class EloquentAccountRepository extends BaseRepository implements AccountRepositoryInterface
{
    public function __construct(Account $model)
    {
        parent::__construct($model);
    }
}
