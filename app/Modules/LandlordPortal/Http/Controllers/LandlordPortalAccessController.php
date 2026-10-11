<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Services\LandlordPortalService;
use App\Modules\Landlords\Models\Landlord;
use Illuminate\Http\RedirectResponse;

/**
 * Staff-side management of a landlord's own portal login - the same
 * shape as App\Modules\TenantPortal\Http\Controllers\
 * PortalAccessController (Module 26), gated by the existing
 * LandlordPolicy 'update' ability rather than a permission of its own.
 */
class LandlordPortalAccessController extends Controller
{
    public function __construct(protected LandlordPortalService $service) {}

    public function enable(Landlord $landlord): RedirectResponse
    {
        $this->authorize('update', $landlord);

        $plainPassword = $this->service->enableAccess($landlord);

        return redirect()->route('landlords.show', $landlord)
            ->with('portal_password', $plainPassword)
            ->with('success', 'Portal access enabled. Share this password with the landlord now - it will not be shown again.');
    }

    public function disable(Landlord $landlord): RedirectResponse
    {
        $this->authorize('update', $landlord);

        $this->service->disableAccess($landlord);

        return redirect()->route('landlords.show', $landlord)->with('success', 'Portal access disabled.');
    }
}
