<?php

namespace App\Modules\LandlordPortal\Http\Middleware;

use App\Modules\Landlords\Models\Landlord;
use App\Modules\LandlordPortal\Support\LandlordAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * `->middleware(EnsureLandlordCan::class.':expenses.viewAny')` - the
 * signed-in landlord's company "Landlord" role must include the
 * permission (Settings -> Roles), on the portal and the app API alike.
 */
class EnsureLandlordCan
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $landlord = $request->user();

        abort_unless($landlord instanceof Landlord && LandlordAccess::can($landlord, $permission), 403, __('Your landlord access does not include this.'));

        return $next($request);
    }
}
