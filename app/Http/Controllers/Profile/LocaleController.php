<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateLocaleRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;

class LocaleController extends Controller
{
    /**
     * Update the user's locale preference.
     */
    public function update(UpdateLocaleRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        // Set locale immediately for current request
        App::setLocale($request->validated('locale'));

        return redirect()
            ->back()
            ->with('toast', [
                'type' => 'success',
                'message' => __('Language updated successfully'),
            ]);
    }
}
