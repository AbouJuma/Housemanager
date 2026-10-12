<?php

namespace App\Modules\Users\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use App\Modules\Users\Http\Requests\StoreRoleRequest;
use App\Modules\Users\Http\Requests\UpdateRoleRequest;
use App\Modules\Users\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(protected RoleService $service) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Role::class);

        // Make sure the company's Landlord role is listed (and editable).
        if ($request->user()->company_id) {
            LandlordAccess::role($request->user()->company_id);
        }

        $roles = $this->service->paginate($request->user()->company_id, $request->string('search')->toString() ?: null);

        return view('users::roles.index', compact('roles'));
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('users::roles.create', ['permissionGroups' => config('permissions')]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->service->create($request->user()->company_id, $request->validated());

        return redirect()->route('roles.edit', $role)->with('success', "Role \"{$role->name}\" created.");
    }

    public function edit(Role $role): View
    {
        $this->authorize('update', $role);

        return view('users::roles.edit', [
            'role' => $role,
            'forLandlords' => LandlordAccess::isLandlordRole($role),
            'permissionGroups' => LandlordAccess::isLandlordRole($role) ? LandlordAccess::PERMISSIONS : config('permissions'),
            'assigned' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->service->update($role, $request->validated());

        return redirect()->route('roles.edit', $role)->with('success', 'Role updated.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        $this->service->delete($role);

        return redirect()->route('roles.index')->with('success', "Role \"{$role->name}\" deleted.");
    }
}
