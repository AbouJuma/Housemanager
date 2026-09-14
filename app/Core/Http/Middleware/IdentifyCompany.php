<?php

namespace App\Core\Http\Middleware;

use App\Core\Tenancy\CompanyContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class IdentifyCompany
{
    public function __construct(
        private readonly CompanyContext $context,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {
    }

    /**
     * $request->user() (no args) resolves against whichever guard
     * Auth::shouldUse() last switched to - already correct for both
     * 'web' (session, appended to the very end of the 'web' group so
     * ordering holds) and Sanctum-authenticated API requests (the
     * 'api-tenant' group runs 'auth:sanctum' - which calls shouldUse()
     * - before this middleware). It's NOT reliable for Module 26's
     * 'tenant'/'landlord' guards specifically (Modules 26/27): a guest
     * hitting a portal route reaches this middleware (also appended to
     * 'web') before any route-level 'auth:tenant'/'auth:landlord'
     * middleware gets a chance to call shouldUse(). Falling back to
     * explicit lookups covers those remaining cases without disturbing
     * the other two - a session guard resolves its own user lazily from
     * session data on every call regardless of when it's asked, the
     * same reasoning already documented in bootstrap/app.php for why
     * appending this middleware to 'web' is safe at all.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user() ?? $request->user('tenant') ?? $request->user('landlord');
        $companyId = ($user !== null && isset($user->company_id) && $user->company_id)
            ? (int) $user->company_id
            : null;

        $this->context->set($companyId);

        // Spatie Permission's teams feature scopes role/permission checks
        // to whatever team id is set here - keeping it in lockstep with
        // CompanyContext means `$user->can(...)` is automatically scoped
        // to the current company everywhere, with no per-module wiring.
        $this->permissionRegistrar->setPermissionsTeamId($companyId);

        return $next($request);
    }
}
