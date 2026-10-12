<?php

namespace App\Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Companies\Models\Company;
use App\Modules\Users\Http\Requests\StoreUserRequest;
use App\Modules\Users\Http\Requests\UpdateUserRequest;
use App\Modules\Users\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(protected UserService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $companyId = $request->user()->isSaasOwner() ? null : $request->user()->company_id;
        $users = $this->service->paginate($companyId, $request->string('search')->toString() ?: null);

        return view('users::index', compact('users'));
    }

    public function create(Request $request): View
    {
        $this->authorize('create', User::class);

        $companies = $request->user()->isSaasOwner()
            ? Company::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        $selectedCompanyId = $request->user()->isSaasOwner()
            ? (int) ($request->input('company_id') ?? old('company_id') ?? 0)
            : $request->user()->company_id;

        $roles = $selectedCompanyId > 0
            ? $this->rolesForCompany($selectedCompanyId)
            : collect();

        return view('users::create', compact('companies', 'roles'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $attributes = $request->validated();

        if (! $request->user()->isSaasOwner()) {
            $attributes['company_id'] = $request->user()->company_id;
        }

        $user = $this->service->create($attributes);

        return redirect()->route('users.show', $user)->with('success', __('User ":name" created. Login - username: :username, password: :password', ['name' => $user->name, 'username' => $user->username, 'password' => $this->service->issuedPassword()]));
    }

    public function show(User $user): View
    {
        $this->authorize('view', $user);

        return view('users::show', compact('user'));
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('users::edit', [
            'user' => $user,
            'roles' => $this->rolesForCompany($user->company_id),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->service->update($user, $request->validated());

        return redirect()->route('users.show', $user)->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $this->service->delete($user);

        return redirect()->route('users.index')->with('success', "User \"{$user->name}\" deleted.");
    }

    protected function rolesForCompany(?int $companyId): Collection
    {
        return Role::query()
            ->where('company_id', $companyId)
            ->where('guard_name', 'web')
            ->get(['id', 'name']);
    }
}
