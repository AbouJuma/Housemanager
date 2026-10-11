<?php

use App\Core\Http\Middleware\IdentifyCompany;
use App\Core\Http\Middleware\NormalizeMoneyInput;
use App\Http\Middleware\SetLocale;
use App\Modules\BuyerPortal\Http\Middleware\EnsureTokenIsBuyer;
use App\Modules\LandlordPortal\Http\Middleware\EnsureTokenIsLandlord;
use App\Modules\Subscriptions\Http\Middleware\EnsureCompanyIsActive;
use App\Modules\TenantPortal\Http\Middleware\EnsureTokenIsTenant;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // "1,250,000" -> "1250000" for money fields, web and API alike.
        $middleware->append(NormalizeMoneyInput::class);

        $middleware->alias([
            'identify.company' => IdentifyCompany::class,
            // Mobile API guard-check: which of the four Sanctum-token-
            // capable models (User/Tenant/Landlord/Buyer) is allowed to
            // hit a given portal's JSON routes - see each class's own
            // docblock.
            'ensure.tenant-token' => EnsureTokenIsTenant::class,
            'ensure.landlord-token' => EnsureTokenIsLandlord::class,
            'ensure.buyer-token' => EnsureTokenIsBuyer::class,
        ]);

        // Safe to append globally here: the session guard resolves the
        // user lazily from cookie data whenever anything calls
        // $request->user(), regardless of where in the 'web' stack this
        // runs, as long as it's after StartSession (it is - appended).
        // EnsureCompanyIsActive (Module 29, ARCHITECTURE.md §22) must
        // run after IdentifyCompany - it reads the CompanyContext that
        // middleware just set.
        $middleware->web(append: [IdentifyCompany::class, EnsureCompanyIsActive::class, SetLocale::class]);
        // The mobile app sends Accept-Language; validation messages follow it.
        $middleware->api(append: [SetLocale::class]);

        // NOT safe to do the same for 'api': Sanctum only makes
        // $request->user() reflect the token-authenticated user AFTER
        // 'auth:sanctum' has run (that middleware calls Auth::shouldUse()).
        // Appending here would run before route-level 'auth:sanctum',
        // so $request->user() would still be null and every module's
        // BelongsToCompany-scoped queries would silently return
        // cross-tenant data over the API. Modules must instead use the
        // 'api-tenant' group below, which gets the order right.
        $middleware->group('api-tenant', [
            'auth:sanctum',
            IdentifyCompany::class,
            EnsureCompanyIsActive::class,
        ]);

        // Modules 26/27 (Tenant/Landlord Portal): an unauthenticated
        // request to a portal route must redirect to that portal's own
        // login page, not the staff one - Laravel's default Authenticate
        // middleware only knows a single 'login' route otherwise.
        // 'landlord-portal*' is checked first since it doesn't start
        // with 'portal', but ordering is kept defensive either way.
        $middleware->redirectGuestsTo(function ($request) {
            if ($request->is('landlord-portal*')) {
                return route('landlord-portal.login');
            }

            return $request->is('portal*') ? route('portal.login') : route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
