<?php

namespace App\Modules\Subscriptions\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Subscriptions\Models\SubscriptionPlan;

class EloquentSubscriptionPlanRepository extends BaseRepository implements SubscriptionPlanRepositoryInterface
{
    public function __construct(SubscriptionPlan $model)
    {
        parent::__construct($model);
    }
}
