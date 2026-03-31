<?php

use App\Providers\FeatureServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        FeatureServiceProvider::class,
    ])
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: fn () => collect([
            __DIR__.'/../routes/system.php',
            __DIR__.'/../routes/api_v1.php',
            __DIR__.'/../routes/api.php',
        ])->each(fn ($path) => require $path),
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\HandleImpersonation::class,
            \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);

        $middleware->alias([
            'feature' => \App\Http\Middleware\EnsureFeatureIsEnabled::class,
            'password.changed' => \App\Http\Middleware\EnsurePasswordChanged::class,
            'impersonate' => \App\Http\Middleware\HandleImpersonation::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
