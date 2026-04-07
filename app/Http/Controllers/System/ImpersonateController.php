<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ImpersonateController extends Controller
{
    /**
     * Start impersonating a user.
     */
    public function start(Request $request, User $user)
    {
        abort_unless($request->user()->is_admin, 403, 'Only admins can impersonate users.');

        // Cannot impersonate another admin
        if ($user->is_admin) {
            return redirect()->back()->withErrors([
                'error' => 'Cannot impersonate another admin user.',
            ]);
        }

        // Store original user ID and target ID in session
        Session::put('original_user_id', Auth::id());
        Session::put('impersonating_id', $user->id);

        // Login as target user
        Auth::login($user);

        return redirect()->route('dashboard')
            ->with('info', "You are now impersonating {$user->name}.");
    }

    /**
     * Stop impersonating and return to original user.
     */
    public function stop(Request $request)
    {
        // Get original user ID
        $originalUserId = Session::get('original_user_id');

        if (! $originalUserId) {
            return redirect()->route('dashboard');
        }

        // Clear impersonation session
        Session::forget(['original_user_id', 'impersonating_id']);

        // Log back in as original user
        $originalUser = User::findOrFail($originalUserId);
        Auth::login($originalUser);

        return redirect()->route('system.users.index')
            ->with('success', 'You are no longer impersonating.');
    }
}
