<?php

namespace App\Modules\TenantPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Tenants\Models\Tenant;
use App\Modules\TenantPortal\Http\Requests\PortalLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Deliberately manual credential lookup instead of Auth::attempt()'s
 * generic array-of-wheres mechanism - see ARCHITECTURE.md §19. Two
 * reasons: (1) tenants.email has no uniqueness constraint globally (only
 * enforced at the moment TenantPortalService::enableAccess() is called),
 * so the lookup must explicitly exclude tenants without portal access
 * rather than risk Hash::check() against a null password; (2) the
 * `tenants` table is scoped by BelongsToCompany's global scope, which is
 * a no-op here anyway (no company context exists yet, pre-login) but
 * withoutGlobalScope makes that explicit rather than accidental.
 */
class PortalAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('tenant')->check()) {
            return redirect()->route('portal.dashboard');
        }

        return view('tenant-portal::auth.login');
    }

    public function login(PortalLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $tenant = Tenant::withoutGlobalScope('company')
            ->whereNotNull('password')
            ->where('email', $credentials['email'])
            ->first();

        if (! $tenant || ! Hash::check($credentials['password'], $tenant->password)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        Auth::guard('tenant')->login($tenant, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('portal.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('tenant')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('portal.login');
    }
}
