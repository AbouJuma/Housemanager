<?php

namespace App\Modules\LandlordPortal\Http\Middleware;

use App\Modules\Landlords\Models\Landlord;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * See App\Modules\TenantPortal\Http\Middleware\EnsureTokenIsTenant's
 * docblock - same reasoning, scoped to the landlord portal's JSON API.
 */
class EnsureTokenIsLandlord
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() instanceof Landlord, 403, 'This endpoint is only available to landlord accounts.');

        return $next($request);
    }
}
