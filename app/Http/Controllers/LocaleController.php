<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    /**
     * Set the application locale via session (for guests).
     */
    public function set(Request $request): RedirectResponse
    {
        $locale = $request->input('locale');

        // Debug: log what we receive
        \Log::info('LocaleController::set - Received locale: ' . $locale);
        \Log::info('LocaleController::set - Request data: ' . json_encode($request->all()));

        // Validate locale
        if (!in_array($locale, ['pt', 'en', 'es'])) {
            $locale = 'pt';
        }

        // Store in session
        session()->put('locale', $locale);

        // Debug: log session
        \Log::info('LocaleController::set - Session locale set to: ' . session('locale'));

        // Set immediately for current request
        App::setLocale($locale);

        return redirect()->back();
    }
}
