<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\FeatureSetting;
use App\Models\User;
use Carbon\Carbon;
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

        // Se não há setting, usa default por ambiente
        if (! $setting) {
            return $this->isFeatureActiveByDefault($featureName);
        }

        // Se há setting inativo, feature desativada
        if (! $setting->is_active) {
            return false;
        }

        return match ($setting->strategy) {
            'all' => true,
            'percentage' => $this->checkPercentage((string) $user->id, $setting->percentage),
            'users' => in_array((string) $user->id, $setting->user_ids ?? []),
            default => false,
        };
    }

    /**
     * Check if a feature is active by default based on environment.
     */
    protected function isFeatureActiveByDefault(string $featureName): bool
    {
        $env = config('app.env');

        // Dev/Local/Testing: todas as features implementadas ativas
        if (in_array($env, ['local', 'development', 'dev', 'testing'])) {
            return true;
        }

        // Production: features até FIRST_DEPLOY_DATE ativas
        $feature = config("features.definitions.{$featureName}");
        $deployDate = config('features.first_deploy_date');

        if (! ($feature['implemented_at'] ?? null) || ! $deployDate) {
            return false;
        }

        return Carbon::parse($feature['implemented_at'])
            ->lte(Carbon::parse($deployDate));
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
