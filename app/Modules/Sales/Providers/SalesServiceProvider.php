<?php

namespace App\Modules\Sales\Providers;

use App\Core\Foundation\ModuleServiceProvider;
use App\Modules\Sales\Models\Buyer;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Policies\BuyerPolicy;
use App\Modules\Sales\Policies\SalePolicy;
use Illuminate\Support\Facades\Gate;

class SalesServiceProvider extends ModuleServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Gate::policy(Sale::class, SalePolicy::class);
        Gate::policy(Buyer::class, BuyerPolicy::class);
    }
}
