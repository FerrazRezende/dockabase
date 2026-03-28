<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\FeatureSetting;
use App\Models\User;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class FeatureServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * Dynamically define all features from config with rollout strategy support.
     */
    public function boot(): void
    {
        $definitions = config('features.definitions', []);

        foreach ($definitions as $featureName => $definition) {
            Feature::define($featureName, function (User $user) use ($featureName) {
                return $this->resolveFeature($user, $featureName);
            });
        }
    }

    /**
     * Resolve feature state for a user based on rollout strategy.
     */
    protected function resolveFeature(User $user, string $featureName): bool
    {
        // God Admin always has access to all features
        if ($user->is_admin === true) {
            return true;
        }

        $setting = FeatureSetting::where('feature_name', $featureName)->first();

        // No setting or inactive = feature is off
        if (!$setting || !$setting->is_active) {
            return false;
        }

        return match ($setting->strategy) {
            'all' => true,
            'percentage' => $this->checkPercentage($user->id, $setting->percentage),
            'users' => in_array($user->id, $setting->user_ids ?? []),
            default => false,
        };
    }

    /**
     * Deterministic percentage check based on user ID hash.
     * Same user always gets the same result for the same percentage.
     */
    protected function checkPercentage(string $userId, int $percentage): bool
    {
        $hash = crc32($userId);
        return ($hash % 100) < $percentage;
    }
}
