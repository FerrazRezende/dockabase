<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\UserStatusUpdated;
use App\Listeners\CacheUserStatusListener;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

final class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],

        UserStatusUpdated::class => [
            CacheUserStatusListener::class,
        ],

        \App\Events\UserAddedToCredential::class => [],
        \App\Events\UserRemovedFromCredential::class => [],
    ];

    public function boot(): void
    {
        //
    }

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
