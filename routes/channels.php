<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Database creation updates - allow any authenticated user
Broadcast::channel('database.{id}', function ($user, $id) {
    return true; // Any authenticated user can subscribe
});
