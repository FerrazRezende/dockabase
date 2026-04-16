<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Database creation updates - allow any authenticated user
Broadcast::channel('database.{id}', function ($user, $id) {
    return true; // Any authenticated user can subscribe
});

// User private channel for status, notifications, etc.
Broadcast::channel('users.{id}', function ($user, string $id) {
    return $user->id === $id;
});

// Global presence channel for status updates - any authenticated user
Broadcast::channel('presence', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});
