<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\FeatureConfigDTO;
use App\Enums\RolloutStrategyEnum;
use App\Models\User;
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

        $this->assertCount(2, $features); // Apenas database-creator e credentials-manager
        $this->assertContainsOnlyInstancesOf(FeatureConfigDTO::class, $features);
    }

    public function test_get_all_features_defaults_to_active_in_local_environment(): void
    {
        // Em ambiente de teste (que usa 'testing'), features são ativas por padrão
        $features = $this->service->getAllFeatures();
        $databaseCreator = $features->first(fn ($f) => $f->name === 'database-creator');

        // Sem setting no banco, usa default do ambiente (testing = true)
        $this->assertTrue($databaseCreator->isActive);
        $this->assertEquals(RolloutStrategyEnum::All, $databaseCreator->strategy);
    }

    public function test_get_feature_returns_null_for_unknown(): void
    {
        $feature = $this->service->getFeature('unknown-feature');

        $this->assertNull($feature);
    }

    public function test_get_feature_returns_dto_for_known(): void
    {
        $feature = $this->service->getFeature('database-creator');

        $this->assertInstanceOf(FeatureConfigDTO::class, $feature);
        $this->assertEquals('database-creator', $feature->name);
        $this->assertEquals('Database Creator', $feature->displayName);
    }

    public function test_is_feature_active_by_default_returns_true_in_dev_environment(): void
    {
        config()->set('app.env', 'local');

        $this->assertTrue($this->service->isFeatureActiveByDefault('database-creator'));
    }

    public function test_is_feature_active_by_default_returns_true_in_production_when_before_deploy_date(): void
    {
        config()->set('app.env', 'production');
        config()->set('features.first_deploy_date', '2026-03-30');

        // database-creator tem implemented_at = 2026-03-15 (antes do deploy)
        $this->assertTrue($this->service->isFeatureActiveByDefault('database-creator'));
    }

    public function test_is_feature_active_by_default_returns_false_in_production_when_after_deploy_date(): void
    {
        config()->set('app.env', 'production');
        config()->set('features.first_deploy_date', '2026-03-01');

        // database-creator tem implemented_at = 2026-03-15 (depois do deploy)
        $this->assertFalse($this->service->isFeatureActiveByDefault('database-creator'));
    }

    public function test_is_feature_active_by_default_returns_false_for_unknown_feature(): void
    {
        config()->set('app.env', 'production');
        config()->set('features.first_deploy_date', '2026-03-30');

        $this->assertFalse($this->service->isFeatureActiveByDefault('unknown-feature'));
    }

    public function test_is_active_for_user_uses_default_when_no_setting_exists(): void
    {
        config()->set('app.env', 'local');
        $user = User::factory()->create(['is_admin' => false]);

        // No FeatureSetting in database - should use environment default (local = true)
        $this->assertTrue($this->service->isActiveForUser('database-creator', $user));
    }
}
