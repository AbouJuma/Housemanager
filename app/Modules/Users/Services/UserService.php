<?php

namespace App\Modules\Users\Services;

use App\Core\Auth\AccountProvisioner;
use App\Core\Auth\LoginIdentity;
use App\Core\Services\BaseService;
use App\Core\Tenancy\PermissionsTeam;
use App\Http\Controllers\Api\AccountController;
use App\Models\User;
use App\Modules\Users\Notifications\AccessChanged;
use App\Modules\Users\Notifications\StaffWelcome;
use App\Modules\Users\Repositories\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @property UserRepositoryInterface $repository see App\Modules\Companies\Services\CompanyService
 *                                               for why this can't be redeclared as a typed constructor property.
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

    protected ?string $issuedPassword = null;

    public function create(array $attributes): User
    {
        // No password chosen: start with their phone number, like every
        // other account (App\Core\Auth\LoginIdentity::defaultPassword).
        $this->issuedPassword = filled($attributes['password'] ?? null)
            ? $attributes['password']
            : LoginIdentity::defaultPassword($attributes['phone'] ?? null);
        $attributes['password'] = Hash::make($this->issuedPassword);

        $roles = $attributes['roles'] ?? null;
        unset($attributes['roles']);

        /** @var User $user */
        $user = DB::transaction(fn () => $this->repository->create($attributes));

        if ($roles !== null) {
            $this->syncRoles($user, $roles, notify: false);
        }

        // Company staff get their sign-in details (SaaS-owner accounts have
        // no company to send from).
        if ($user->company_id) {
            $user->notify(new StaffWelcome([
                'staff_name' => $user->name,
                'company_name' => $user->company->name,
                ...app(AccountProvisioner::class)->credentials($user, $this->issuedPassword, AccountProvisioner::loginUrlFor('staff')),
            ]));
        }

        return $user;
    }

    /**
     * The plain password the last create() set - shown once to whoever
     * created the account, never stored.
     */
    public function issuedPassword(): ?string
    {
        return $this->issuedPassword;
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

    /**
     * @param  bool  $notify  tell the person what changed (not on account
     *                        creation - the welcome message covers that)
     */
    public function syncRoles(User $user, array $roleNames, bool $notify = true): User
    {
        $before = AccountController::permissionsOf($user);
        $rolesBefore = $user->company_id ? PermissionsTeam::runAs($user->company_id, fn () => $user->getRoleNames()->all()) : [];

        PermissionsTeam::runAs($user->company_id, fn () => $user->syncRoles($roleNames));

        $user = $user->fresh();

        if ($notify && $user->company_id) {
            $after = AccountController::permissionsOf($user);
            $rolesAfter = PermissionsTeam::runAs($user->company_id, fn () => $user->getRoleNames()->all());
            $granted = array_values(array_diff($after, $before));
            $revoked = array_values(array_diff($before, $after));

            if ($granted || $revoked || array_diff($rolesAfter, $rolesBefore) || array_diff($rolesBefore, $rolesAfter)) {
                $user->notify(new AccessChanged($granted, $revoked, $rolesAfter));
            }
        }

        return $user;
    }
}
