<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Landlords\Models\Landlord;
use App\Modules\LandlordPortal\Http\Requests\LandlordPortalLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Same manual credential lookup as App\Modules\TenantPortal\Http\
 * Controllers\PortalAuthController (Module 26, ARCHITECTURE.md §19) and
 * for the identical reason: landlords.email has no database-level
 * uniqueness constraint, only enforced among portal-enabled landlords by
 * LandlordPortalService::enableAccess().
 */
class LandlordPortalAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('landlord')->check()) {
            return redirect()->route('landlord-portal.dashboard');
        }

        return view('landlord-portal::auth.login');
    }

    public function login(LandlordPortalLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        $landlord = Landlord::withoutGlobalScope('company')
            ->whereNotNull('password')
            ->where('email', $credentials['email'])
            ->first();

        if (! $landlord || ! Hash::check($credentials['password'], $landlord->password)) {
            throw ValidationException::withMessages(['email' => 'These credentials do not match our records.']);
        }

        Auth::guard('landlord')->login($landlord, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('landlord-portal.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('landlord')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landlord-portal.login');
    }
}
