<?php

namespace App\Modules\Sales\Policies;

use App\Models\User;
use App\Modules\Sales\Models\Sale;

class SalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('sales.viewAny');
    }

    public function view(User $user, Sale $sale): bool
    {
        return $user->company_id === $sale->company_id && $user->can('sales.view');
    }

    public function create(User $user): bool
    {
        return $user->can('sales.create');
    }

    public function update(User $user, Sale $sale): bool
    {
        return $user->company_id === $sale->company_id && $user->can('sales.update');
    }

    public function delete(User $user, Sale $sale): bool
    {
        return $user->company_id === $sale->company_id && $user->can('sales.delete');
    }
}
