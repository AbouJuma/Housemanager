<?php

namespace App\Modules\Sales\Policies;

use App\Models\User;
use App\Modules\Sales\Models\Buyer;

class BuyerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('buyers.viewAny');
    }

    public function view(User $user, Buyer $buyer): bool
    {
        return $user->company_id === $buyer->company_id && $user->can('buyers.view');
    }

    public function create(User $user): bool
    {
        return $user->can('buyers.create');
    }

    public function update(User $user, Buyer $buyer): bool
    {
        return $user->company_id === $buyer->company_id && $user->can('buyers.update');
    }

    public function delete(User $user, Buyer $buyer): bool
    {
        return $user->company_id === $buyer->company_id && $user->can('buyers.delete');
    }
}
