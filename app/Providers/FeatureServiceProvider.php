<?php

declare(strict_types=1);

namespace App\Providers;

use App\Features\AdvancedRbac;
use App\Features\AutomatedBackups;
use App\Features\CredentialsManager;
use App\Features\DatabaseCreator;
use App\Features\DatabaseEncryption;
use App\Features\DynamicApi;
use App\Features\OtpAuth;
use App\Features\Realtime;
use App\Features\Rls;
use App\Features\SchemaBuilder;
use App\Features\Storage;
use App\Features\TableManager;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class FeatureServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * Register all features using class-based Pennant features.
     * Pennant reads the $name property on each class to determine
     * the storage/lookup name (e.g., 'database-creator').
     *
     * Each feature class contains resolve() and before() methods that handle
     * rollout strategies, admin overrides, and environment defaults.
     */
    public function boot(): void
    {
        Feature::define(DatabaseCreator::class);
        Feature::define(CredentialsManager::class);
        Feature::define(SchemaBuilder::class);
        Feature::define(TableManager::class);
        Feature::define(DynamicApi::class);
        Feature::define(Realtime::class);
        Feature::define(Storage::class);
        Feature::define(OtpAuth::class);
        Feature::define(DatabaseEncryption::class);
        Feature::define(AutomatedBackups::class);
        Feature::define(Rls::class);
        Feature::define(AdvancedRbac::class);
    }
}
