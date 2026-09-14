<?php

namespace App\Modules\Inventory\Repositories;

use App\Core\Repositories\BaseRepository;
use App\Modules\Inventory\Models\InventoryItem;

class EloquentInventoryItemRepository extends BaseRepository implements InventoryItemRepositoryInterface
{
    public function __construct(InventoryItem $model)
    {
        parent::__construct($model);
    }
}
