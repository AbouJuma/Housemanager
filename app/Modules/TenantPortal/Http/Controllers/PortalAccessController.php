<?php

namespace App\Modules\TenantPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\TenantPortal\Services\TenantPortalService;
use App\Modules\Tenants\Models\Tenant;
use Illuminate\Http\RedirectResponse;

/**
 * Staff-side (the 'web' guard, TenantPolicy) management of a tenant's
 * OWN portal login - deliberately not gated by any permission of its
 * own, the same "fold into the existing update permission" restraint
 * as MaintenanceRequest's recordExpense (ARCHITECTURE.md §15): whoever
 * can already edit a tenant can manage their portal access too.
 */
class PortalAccessController extends Controller
{
    public function __construct(protected TenantPortalService $service) {}

    public function enable(Tenant $tenant): RedirectResponse
    {
        $this->authorize('update', $tenant);

        $plainPassword = $this->service->enableAccess($tenant);

        return redirect()->route('tenants.show', $tenant)
            ->with('portal_password', $plainPassword)
            ->with('success', 'Portal access enabled. Share this password with the tenant now - it will not be shown again.');
    }

    public function disable(Tenant $tenant): RedirectResponse
    {
        $this->authorize('update', $tenant);

        $this->service->disableAccess($tenant);

        return redirect()->route('tenants.show', $tenant)->with('success', 'Portal access disabled.');
    }
}
