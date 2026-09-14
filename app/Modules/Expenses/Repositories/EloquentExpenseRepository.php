<?php

namespace App\Modules\Expenses\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Expenses\Models\Expense;

class EloquentExpenseRepository extends BaseRepository implements ExpenseRepositoryInterface
{
    public function __construct(Expense $model)
    {
        parent::__construct($model);
    }
}
