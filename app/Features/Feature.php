<?php

declare(strict_types=1);

namespace App\Features;

use App\Enums\RolloutStrategyEnum;
use App\Models\FeatureSetting;
use App\Models\User;
use Carbon\Carbon;

abstract class Feature
{
    /**
     * The feature name used for config lookup and database storage.
     * Override this in each concrete feature class.
     */
    protected string $name = '';

    /**
     * Run an in-memory check before the stored value is retrieved.
     * God Admin always bypasses all feature checks.
     */
    public function before(User $user): mixed
    {
        if ($user->is_admin === true) {
            return true;
        }

        return null;
    }

    /**
     * Resolve the feature's initial value for the given user.
     * Checks database settings first, then falls back to environment defaults.
     */
    public function resolve(User $user): mixed
    {
        $setting = FeatureSetting::where('feature_name', $this->name)->first();

        if (! $setting) {
            return $this->isActiveByDefault();
        }

        if (! $setting->is_active) {
            return false;
        }

        return match ($setting->strategy) {
            RolloutStrategyEnum::ALL => true,
            RolloutStrategyEnum::PERCENTAGE => $this->checkPercentage((string) $user->id, $setting->percentage),
            RolloutStrategyEnum::USERS => in_array((string) $user->id, $setting->user_ids ?? []),
            RolloutStrategyEnum::INACTIVE => false,
        };
    }

    /**
     * Check if this feature is active by default based on environment.
     * - Dev/Local/Testing: implemented features are active
     * - Production: features up to FIRST_DEPLOY_DATE are active
     */
    protected function isActiveByDefault(): bool
    {
        $feature = config("features.definitions.{$this->name}");

        if (! ($feature['implemented_at'] ?? null)) {
            return false;
        }

        $env = config('app.env');

        if (in_array($env, ['local', 'development', 'dev', 'testing'])) {
            return true;
        }

        $deployDate = config('features.first_deploy_date');

        if (! $deployDate) {
            return false;
        }

        return Carbon::parse($feature['implemented_at'])
            ->lte(Carbon::parse($deployDate));
    }

    /**
     * Deterministic percentage check based on user ID hash.
     * Same user always gets the same result for the same percentage.
     */
    public static function checkPercentage(string $userId, int $percentage): bool
    {
        $hash = crc32($userId);

        return ($hash % 100) < $percentage;
    }
}
