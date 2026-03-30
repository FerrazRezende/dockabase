<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\UserCollection;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): UserCollection
    {
        $users = User::select(['id', 'name', 'email', 'is_admin'])
            ->orderBy('name')
            ->get();

        return new UserCollection($users);
    }
}
