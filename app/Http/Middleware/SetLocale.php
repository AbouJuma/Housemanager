<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Picks the interface language for this request - English or Kiswahili.
 *
 * Web: the switcher's choice (session), else the signed-in person's saved
 * choice, else their company's default language, else the app default.
 * API (mobile app): the Accept-Language header it sends, else the same
 * account/company fallbacks.
 */
class SetLocale
{
    public const SUPPORTED = ['en', 'sw'];

    public function handle(Request $request, Closure $next): Response
    {
        app()->setLocale($this->resolve($request));

        return $next($request);
    }

    protected function resolve(Request $request): string
    {
        $candidates = [];

        if ($request->hasSession()) {
            $candidates[] = $request->session()->get('locale');
        } else {
            $candidates[] = $request->getPreferredLanguage(self::SUPPORTED);
            $header = (string) $request->header('Accept-Language');
            if ($header === '' || $header === '*') {
                array_pop($candidates);
            }
        }

        $account = $this->account();
        $candidates[] = $account?->locale;
        $candidates[] = $account?->company?->locale;
        $candidates[] = config('app.locale');

        foreach ($candidates as $locale) {
            $short = is_string($locale) ? strtolower(substr($locale, 0, 2)) : null;
            if ($short && in_array($short, self::SUPPORTED, true)) {
                return $short;
            }
        }

        return 'en';
    }

    protected function account(): mixed
    {
        foreach (['web', 'tenant', 'landlord', 'buyer', 'sanctum'] as $guard) {
            try {
                if ($user = Auth::guard($guard)->user()) {
                    return $user;
                }
            } catch (\Throwable) {
                // Guard not usable on this request type.
            }
        }

        return null;
    }
}
