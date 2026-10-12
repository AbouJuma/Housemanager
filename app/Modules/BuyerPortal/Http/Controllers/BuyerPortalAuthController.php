<?php

namespace App\Modules\BuyerPortal\Http\Controllers;

use App\Core\Auth\WebSignIn;
use App\Http\Controllers\Controller;
use App\Modules\BuyerPortal\Http\Requests\BuyerPortalLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Sign in / out of the buyer web portal (the 'buyer' guard). */
class BuyerPortalAuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::guard('buyer')->check()) {
            return redirect()->route('buyer-portal.dashboard');
        }

        return view('buyer-portal::auth.login');
    }

    public function login(BuyerPortalLoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        // Email, username or phone - and anyone else's details take them
        // to their own portal (App\Core\Auth\WebSignIn).
        $redirect = WebSignIn::attempt($request, 'buyer', $credentials['email'], $credentials['password']);

        if (! $redirect) {
            throw ValidationException::withMessages(['email' => __('These credentials do not match our records.')]);
        }

        return $redirect;
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('buyer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('buyer-portal.login');
    }
}
