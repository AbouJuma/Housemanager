<?php

namespace App\Modules\Subscriptions\Http\Middleware;

use App\Core\Tenancy\CompanyContext;
use App\Modules\Companies\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The real payoff of this module: `companies.status` (trial/active/
 * suspended/cancelled, present since Module 2) had never actually been
 * enforced anywhere - isActive()/isOnTrial() existed as dead helpers.
 * This middleware is what makes suspending or cancelling a company
 * actually cut off access, for every guard uniformly: staff (`web`),
 * tenants, and landlords alike, since CompanyContext::id() (set by
 * IdentifyCompany, ARCHITECTURE.md §19) already resolves correctly
 * across all three. Reads CompanyContext rather than $request->user()
 * directly for exactly that reason - one check covers every guard for
 * free, the same reasoning IdentifyCompany itself already established.
 *
 * SaaS-owner staff (no company context at all) and guests are always
 * exempt - trial is deliberately NOT blocked here, only suspended/
 * cancelled; a trialing company must keep full access for its trial to
 * mean anything.
 */
class EnsureCompanyIsActive
{
    /**
     * @var list<string> a suspended/cancelled company's own users must
     *  always be able to log out - blocking that route too would leave
     *  them stuck on a 403 page with no way off it.
     */
    protected const EXEMPT_ROUTES = ['logout', 'portal.logout', 'landlord-portal.logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $companyId = CompanyContext::id();

        if ($companyId === null || $request->routeIs(...self::EXEMPT_ROUTES)) {
            return $next($request);
        }

        $company = Company::query()->find($companyId);

        if ($company && in_array($company->status, ['suspended', 'cancelled'], true)) {
            abort(403, 'This account is currently '.$company->status.'. Please contact support.');
        }

        return $next($request);
    }
}
