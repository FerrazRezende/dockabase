<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class FeatureServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * Auto-discover all feature classes in App\Features\ namespace.
     * The abstract base class lives in App\Features\Base\ so
     * discover() (depth 0) only finds concrete feature classes.
     */
    public function boot(): void
    {
        Feature::discover();
    }
}
