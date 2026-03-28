<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\FeatureConfigDTO;
use App\Enums\RolloutStrategyEnum;
use App\Services\FeatureFlagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagServiceTest extends TestCase
{
    use RefreshDatabase;

    private FeatureFlagService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FeatureFlagService::class);
    }

    public function test_get_all_features_returns_all_defined_features(): void
    {
        $features = $this->service->getAllFeatures();

        $this->assertCount(8, $features);
        $this->assertContainsOnlyInstancesOf(FeatureConfigDTO::class, $features);
    }

    public function test_get_all_features_defaults_to_inactive(): void
    {
        $features = $this->service->getAllFeatures();
        $realtime = $features->first(fn ($f) => $f->name === 'realtime');

        $this->assertFalse($realtime->isActive);
        $this->assertEquals(RolloutStrategyEnum::Inactive, $realtime->strategy);
    }

    public function test_get_feature_returns_null_for_unknown(): void
    {
        $feature = $this->service->getFeature('unknown-feature');

        $this->assertNull($feature);
    }

    public function test_get_feature_returns_dto_for_known(): void
    {
        $feature = $this->service->getFeature('dynamic-api');

        $this->assertInstanceOf(FeatureConfigDTO::class, $feature);
        $this->assertEquals('dynamic-api', $feature->name);
        $this->assertEquals('Dynamic REST API', $feature->displayName);
    }
}
