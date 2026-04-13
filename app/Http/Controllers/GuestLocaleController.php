<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class GuestLocaleController extends Controller
{
    /**
     * Set the application locale via session (for guests).
     */
    public function set(Request $request): RedirectResponse
    {
        $locale = $request->input('locale');

        // Validate locale
        if (!in_array($locale, ['pt', 'en', 'es'])) {
            $locale = 'pt';
        }

        // Store in session
        session()->put('locale', $locale);

        // Set immediately for current request
        App::setLocale($locale);

        return redirect()->back();
    }
}
