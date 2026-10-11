<?php

namespace App\Modules\LandlordPortal\Http\Controllers;

use App\Core\Auth\WebSignIn;
use App\Http\Controllers\Controller;
use App\Modules\LandlordPortal\Http\Requests\LandlordPortalLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Email, username or phone - and if these are a staff member's or
        // another portal's details, sign them in there instead
        // (App\Core\Auth\WebSignIn).
        $redirect = WebSignIn::attempt($request, 'landlord', $credentials['email'], $credentials['password']);

        if (! $redirect) {
            throw ValidationException::withMessages(['email' => __('These credentials do not match our records.')]);
        }

        return $redirect;
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('landlord')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('landlord-portal.login');
    }
}
