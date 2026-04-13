<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareTranslationsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Get the current locale
        $locale = App::getLocale();

        // Load the translation file for the current locale
        $translationFile = lang_path("{$locale}.json");

        if (file_exists($translationFile)) {
            $translations = json_decode(file_get_contents($translationFile), true);

            // Share translations with Inertia BEFORE the response is generated
            Inertia::share('translations', $translations);
            Inertia::share('locale', $locale);
        } else {
            Inertia::share('translations', []);
            Inertia::share('locale', $locale);
        }

        return $next($request);
    }
}
