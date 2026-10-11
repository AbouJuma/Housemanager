<?php

namespace App\Modules\BuyerPortal\Http\Middleware;

use App\Modules\Sales\Models\Buyer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * See App\Modules\TenantPortal\Http\Middleware\EnsureTokenIsTenant's
 * docblock - same reasoning, scoped to the buyer portal's JSON API.
 */
class EnsureTokenIsBuyer
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() instanceof Buyer, 403, 'This endpoint is only available to buyer accounts.');

        return $next($request);
    }
}
