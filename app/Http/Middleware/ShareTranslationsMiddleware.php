<?php

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
        $response = $next($request);

        // Only share translations for Inertia requests
        if (!$request->header('X-Inertia')) {
            return $response;
        }

        // Get the current locale
        $locale = App::getLocale();

        // Load the translation file for the current locale
        $translationFile = lang_path("{$locale}.json");

        if (file_exists($translationFile)) {
            $translations = json_decode(file_get_contents($translationFile), true);

            // Share translations with Inertia
            Inertia::share('translations', $translations);

            // Debug: log locale
            \Log::info('Locale set to: ' . $locale . ', translations count: ' . count($translations));
        } else {
            Inertia::share('translations', []);
            \Log::warning('Translation file not found: ' . $translationFile);
        }

        // Share current locale with Inertia
        Inertia::share('locale', $locale);

        return $response;
    }
}
