<?php

namespace App\Modules\Companies\Policies;

use App\Models\User;
use App\Modules\Companies\Models\Company;

/**
 * SaaS-owner staff (users with a null company_id, per the tenancy model
 * in docs/ARCHITECTURE.md) manage every company; a company's own users
 * may only view/update the single company they belong to.
 */
class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isSaasOwner();
    }

    public function view(User $user, Company $company): bool
    {
        return $user->isSaasOwner() || $user->company_id === $company->id;
    }

    public function create(User $user): bool
    {
        return $user->isSaasOwner();
    }

    public function update(User $user, Company $company): bool
    {
        return $user->isSaasOwner() || $user->company_id === $company->id;
    }

    public function delete(User $user, Company $company): bool
    {
        return $user->isSaasOwner();
    }
}
