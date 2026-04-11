<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Database creation updates - allow any authenticated user
Broadcast::channel('database.{id}', function ($user, $id) {
    return true; // Any authenticated user can subscribe
});

// User status channel for real-time presence
Broadcast::channel('private-users.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Global presence channel for status updates - any authenticated user
Broadcast::channel('private-presence', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});
