<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\SystemUserCollection;
use App\Http\Resources\UserCollection;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): UserCollection
    {
        $users = User::select(['id', 'name', 'email', 'is_admin'])
            ->orderBy('name')
            ->get();

        return new UserCollection($users);
    }

    /**
     * List all users for admin panel.
     */
    public function indexForAdmin(Request $request): Response
    {
        $users = User::select(['id', 'name', 'email', 'is_admin', 'created_at', 'updated_at'])
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('System/Users/Index', [
            'users' => json_decode((new SystemUserCollection($users))->toJson(), true),
        ]);
    }
}
