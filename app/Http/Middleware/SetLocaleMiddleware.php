<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SetLocaleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = null;

        // For authenticated users, use their preference from database
        if ($request->user()?->locale) {
            $locale = $request->user()->locale;
        }
        // For guests, check session first (set by locale.set route)
        elseif ($request->session()->has('locale')) {
            $sessionLocale = $request->session()->get('locale');
            if (in_array($sessionLocale, ['pt', 'en', 'es'], true)) {
                $locale = $sessionLocale;
            }
        }
        // Then check cookie
        elseif ($request->hasCookie('locale')) {
            $cookieLocale = $request->cookie('locale');
            if (in_array($cookieLocale, ['pt', 'en', 'es'], true)) {
                $locale = $cookieLocale;
            }
        }

        // Fallback to app config
        if ($locale === null) {
            $locale = config('app.locale', 'pt');
        }

        // Validate locale is supported
        if (!in_array($locale, ['pt', 'en', 'es'], true)) {
            $locale = config('app.fallback_locale', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
