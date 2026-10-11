<?php

namespace App\Modules\TenantPortal\Http\Middleware;

use App\Modules\Tenants\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sanctum's 'auth:sanctum' guard authenticates whichever model owns the
 * bearer token (User, Tenant, Landlord, or Buyer all have HasApiTokens)
 * - nothing about that middleware alone stops a Landlord's or Buyer's
 * token from being accepted on a tenant-only endpoint. This closes that
 * gap for the tenant portal's JSON API routes specifically.
 */
class EnsureTokenIsTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless($request->user() instanceof Tenant, 403, 'This endpoint is only available to tenant accounts.');

        return $next($request);
    }
}
