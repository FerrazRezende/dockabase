<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\FeatureConfigDTO;
use App\Enums\RolloutStrategyEnum;
use App\Models\FeatureHistory;
use App\Models\FeatureSetting;
use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;

class FeatureFlagService
{
    /**
     * Get all available features with their current status.
     *
     * @return Collection<int, FeatureConfigDTO>
     */
    public function getAllFeatures(): Collection
    {
        $definitions = config('features.definitions', []);
        $settings = FeatureSetting::all()->keyBy('feature_name');

        return collect($definitions)
            ->map(function (array $definition, string $name) use ($settings) {
                $setting = $settings->get($name);

                return FeatureConfigDTO::fromDefinition(
                    name: $name,
                    definition: $definition,
                    strategy: $setting?->strategy,
                    isActive: $setting?->is_active ?? false,
                    percentage: $setting?->percentage ?? 0,
                    userIds: $setting?->user_ids
                );
            })
            ->values();
    }

    /**
     * Get a single feature's configuration.
     */
    public function getFeature(string $featureName): ?FeatureConfigDTO
    {
        $definition = config("features.definitions.{$featureName}");

        if (! $definition) {
            return null;
        }

        $setting = FeatureSetting::where('feature_name', $featureName)->first();

        return FeatureConfigDTO::fromDefinition(
            name: $featureName,
            definition: $definition,
            strategy: $setting?->strategy,
            isActive: $setting?->is_active ?? false,
            percentage: $setting?->percentage ?? 0,
            userIds: $setting?->user_ids
        );
    }

    /**
     * Activate a feature with the given strategy.
     */
    public function activate(string $featureName, array $options, User $actor): FeatureConfigDTO
    {
        $definition = config("features.definitions.{$featureName}");
        abort_unless($definition, 404, "Feature {$featureName} not found");

        $strategy = RolloutStrategyEnum::from($options['strategy'] ?? 'all');
        $percentage = $options['percentage'] ?? 0;
        $userIds = $options['user_ids'] ?? null;

        $setting = FeatureSetting::firstOrCreate(
            ['feature_name' => $featureName],
            ['strategy' => RolloutStrategyEnum::Inactive, 'is_active' => false]
        );

        $previousState = $setting->toArray();

        $setting->update([
            'strategy' => $strategy,
            'percentage' => $percentage,
            'user_ids' => $userIds,
            'is_active' => true,
        ]);

        $this->recordHistory($setting, 'activated', $actor, $previousState, $setting->fresh()->toArray());

        // Purge Pennant cache so feature gets re-resolved
        Feature::purge($featureName);

        return $this->getFeature($featureName);
    }

    /**
     * Deactivate a feature.
     */
    public function deactivate(string $featureName, User $actor): FeatureConfigDTO
    {
        $definition = config("features.definitions.{$featureName}");
        abort_unless($definition, 404, "Feature {$featureName} not found");

        $setting = FeatureSetting::firstOrCreate(
            ['feature_name' => $featureName],
            ['strategy' => RolloutStrategyEnum::Inactive, 'is_active' => false]
        );

        $previousState = $setting->toArray();

        $setting->update([
            'strategy' => RolloutStrategyEnum::Inactive,
            'percentage' => 0,
            'is_active' => false,
        ]);

        $this->recordHistory($setting, 'deactivated', $actor, $previousState, $setting->fresh()->toArray());

        // Purge Pennant cache so feature gets re-resolved
        Feature::purge($featureName);

        return $this->getFeature($featureName);
    }

    /**
     * Update feature rollout settings.
     */
    public function update(string $featureName, array $options, User $actor): FeatureConfigDTO
    {
        $definition = config("features.definitions.{$featureName}");
        abort_unless($definition, 404, "Feature {$featureName} not found");

        $setting = FeatureSetting::where('feature_name', $featureName)->firstOrFail();
        $previousState = $setting->toArray();

        $updateData = array_filter([
            'percentage' => $options['percentage'] ?? null,
            'user_ids' => $options['user_ids'] ?? null,
        ], fn ($v) => $v !== null);

        if (! empty($updateData)) {
            $setting->update($updateData);
            $this->recordHistory($setting, 'updated', $actor, $previousState, $setting->fresh()->toArray());

            // Purge Pennant cache so feature gets re-resolved
            Feature::purge($featureName);
        }

        return $this->getFeature($featureName);
    }

    /**
     * Add a user to the feature's allowlist.
     */
    public function addUser(string $featureName, string $userId, User $actor): FeatureConfigDTO
    {
        $setting = FeatureSetting::where('feature_name', $featureName)->firstOrFail();
        $previousState = $setting->toArray();

        $userIds = $setting->user_ids ?? [];
        if (! in_array($userId, $userIds)) {
            $userIds[] = $userId;
            $setting->update(['user_ids' => $userIds]);
            $this->recordHistory($setting, 'updated', $actor, $previousState, $setting->fresh()->toArray());

            // Purge Pennant cache so feature gets re-resolved
            Feature::purge($featureName);
        }

        return $this->getFeature($featureName);
    }

    /**
     * Remove a user from the feature's allowlist.
     */
    public function removeUser(string $featureName, string $userId, User $actor): FeatureConfigDTO
    {
        $setting = FeatureSetting::where('feature_name', $featureName)->firstOrFail();
        $previousState = $setting->toArray();

        $userIds = $setting->user_ids ?? [];
        $userIds = array_values(array_diff($userIds, [$userId]));

        $setting->update(['user_ids' => $userIds ?: null]);
        $this->recordHistory($setting, 'updated', $actor, $previousState, $setting->fresh()->toArray());

        // Purge Pennant cache so feature gets re-resolved
        Feature::purge($featureName);

        return $this->getFeature($featureName);
    }

    /**
     * Check if a feature is active for a specific user.
     */
    public function isActiveForUser(string $featureName, User $user): bool
    {
        // God Admin always sees all features
        if ($user->is_admin === true) {
            return true;
        }

        $setting = FeatureSetting::where('feature_name', $featureName)->first();

        if (! $setting || ! $setting->is_active) {
            return false;
        }

        return match ($setting->strategy) {
            RolloutStrategyEnum::All => true,
            RolloutStrategyEnum::Percentage => $this->checkPercentage($user->id, $setting->percentage),
            RolloutStrategyEnum::Users => in_array($user->id, $setting->user_ids ?? []),
            RolloutStrategyEnum::Inactive => false,
        };
    }

    /**
     * Get features active for a specific user.
     */
    public function getActiveFeaturesForUser(User $user): array
    {
        $allFeatures = $this->getAllFeatures();

        return $allFeatures
            ->filter(fn (FeatureConfigDTO $feature) => $this->isActiveForUser($feature->name, $user))
            ->map(fn (FeatureConfigDTO $feature) => $feature->name)
            ->values()
            ->toArray();
    }

    /**
     * Get history for a feature.
     */
    public function getHistory(string $featureName): Collection
    {
        $setting = FeatureSetting::where('feature_name', $featureName)->first();

        if (! $setting) {
            return collect();
        }

        return $setting->histories()
            ->with('actor')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn (FeatureHistory $history) => [
                'id' => $history->id,
                'action' => $history->action,
                'actor' => $history->actor->name ?? $history->actor->email,
                'previous_state' => $history->previous_state,
                'new_state' => $history->new_state,
                'created_at' => $history->created_at->toISOString(),
            ]);
    }

    /**
     * Deterministic percentage check based on user ID hash.
     */
    private function checkPercentage(string $userId, int $percentage): bool
    {
        $hash = crc32($userId);

        return ($hash % 100) < $percentage;
    }

    /**
     * Record a change in feature history.
     */
    private function recordHistory(FeatureSetting $setting, string $action, User $actor, ?array $previous, ?array $new): void
    {
        FeatureHistory::create([
            'feature_setting_id' => $setting->id,
            'action' => $action,
            'actor_id' => $actor->id,
            'previous_state' => $previous,
            'new_state' => $new,
        ]);
    }
}
