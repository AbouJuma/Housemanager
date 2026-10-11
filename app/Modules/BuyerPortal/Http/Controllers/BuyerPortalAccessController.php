<?php

namespace App\Modules\BuyerPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\BuyerPortal\Services\BuyerPortalService;
use App\Modules\Sales\Models\Buyer;
use Illuminate\Http\RedirectResponse;

/**
 * Staff-side (the 'web' guard, BuyerPolicy) management of a buyer's OWN
 * portal login - mirrors App\Modules\TenantPortal\Http\Controllers\
 * PortalAccessController exactly, including the "fold into the existing
 * update permission" restraint: whoever can already edit a buyer can
 * manage their portal access too.
 */
class BuyerPortalAccessController extends Controller
{
    public function __construct(protected BuyerPortalService $service) {}

    public function enable(Buyer $buyer): RedirectResponse
    {
        $this->authorize('update', $buyer);

        $plainPassword = $this->service->enableAccess($buyer);

        return redirect()->route('buyers.show', $buyer)
            ->with('portal_password', $plainPassword)
            ->with('success', 'Portal access enabled. Share this password with the buyer now - it will not be shown again.');
    }

    public function disable(Buyer $buyer): RedirectResponse
    {
        $this->authorize('update', $buyer);

        $this->service->disableAccess($buyer);

        return redirect()->route('buyers.show', $buyer)->with('success', 'Portal access disabled.');
    }
}
