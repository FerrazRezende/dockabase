<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\FeatureConfigDTO;
use Illuminate\Support\Collection;

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

        return collect($definitions)
            ->map(fn (array $definition, string $name) => FeatureConfigDTO::fromDefinition(
                name: $name,
                definition: $definition
            ))
            ->values();
    }

    /**
     * Get a single feature's configuration.
     */
    public function getFeature(string $featureName): ?FeatureConfigDTO
    {
        $definition = config("features.definitions.{$featureName}");

        if (!$definition) {
            return null;
        }

        return FeatureConfigDTO::fromDefinition(
            name: $featureName,
            definition: $definition
        );
    }
}
