<?php

namespace App\Modules\Inventory\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Inventory\Models\InventoryItem;
use App\Modules\Inventory\Policies\InventoryItemPolicy;
use App\Modules\Inventory\Repositories\EloquentInventoryItemRepository;
use App\Modules\Inventory\Repositories\InventoryItemRepositoryInterface;
use Illuminate\Support\Facades\Gate;

class InventoryServiceProvider extends ModuleServiceProvider
{
    public function register(): void
    {
        $this->app->bind(InventoryItemRepositoryInterface::class, EloquentInventoryItemRepository::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(InventoryItem::class, InventoryItemPolicy::class);
    }
}
