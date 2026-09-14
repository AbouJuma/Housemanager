<?php

namespace App\Modules\Users\Services;

use App\Core\Services\BaseService;
use App\Core\Tenancy\PermissionsTeam;
use App\Models\User;
use App\Modules\Users\Repositories\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @property UserRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *  for why this can't be redeclared as a typed constructor property.
 */
class UserService extends BaseService
{
    public function __construct(UserRepositoryInterface $repository)
    {
        parent::__construct($repository);
    }

    public function paginate(?int $companyId, ?string $search, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when($search, fn ($query) => $query->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $attributes): User
    {
        $attributes['password'] = Hash::make($attributes['password']);

        $roles = $attributes['roles'] ?? null;
        unset($attributes['roles']);

        /** @var User $user */
        $user = DB::transaction(fn () => $this->repository->create($attributes));

        if ($roles !== null) {
            $this->syncRoles($user, $roles);
        }

        return $user;
    }

    public function update(Model $model, array $attributes): User
    {
        if (! empty($attributes['password'])) {
            $attributes['password'] = Hash::make($attributes['password']);
        } else {
            unset($attributes['password']);
        }

        $roles = $attributes['roles'] ?? null;
        unset($attributes['roles']);

        /** @var User $user */
        $user = DB::transaction(fn () => $this->repository->update($model, $attributes));

        if ($roles !== null) {
            $this->syncRoles($user, $roles);
        }

        return $user;
    }

    public function syncRoles(User $user, array $roleNames): User
    {
        PermissionsTeam::runAs($user->company_id, fn () => $user->syncRoles($roleNames));

        return $user->fresh();
    }
}
