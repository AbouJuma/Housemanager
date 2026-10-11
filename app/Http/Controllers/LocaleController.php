<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * The language switcher (English / Kiswahili) - remembered for this
 * session, and on the signed-in person's account so it follows them to
 * other devices and the mobile app.
 */
class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, SetLocale::SUPPORTED, true), 404);

        $request->session()->put('locale', $locale);

        foreach (['web', 'tenant', 'landlord', 'buyer'] as $guard) {
            if ($user = Auth::guard($guard)->user()) {
                $user->forceFill(['locale' => $locale])->save();
            }
        }

        return back();
    }
}
