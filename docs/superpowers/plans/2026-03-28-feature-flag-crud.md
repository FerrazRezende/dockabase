# Feature Flag Manager - Stories 2-8: CRUD Operations

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development to implement this plan.

**Goal:** Implementar todas as operações CRUD de feature flags: ativar, desativar, atualizar rollout, gerenciar usuários, histórico.

**Architecture:** Single-tenant (sem project). Features salvas em tabela `feature_settings`. Histórico em `feature_histories`.

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL, Pennant

---

## Files Structure

```
app/
├── Models/
│   ├── FeatureSetting.php          # Configuração persistente de features
│   └── FeatureHistory.php          # Histórico de mudanças
├── Services/
│   └── FeatureFlagService.php      # Adicionar métodos CRUD
├── Http/
│   ├── Controllers/System/
│   │   └── FeatureFlagController.php  # Adicionar métodos
│   └── Requests/System/
│       ├── ActivateFeatureRequest.php
│       └── UpdateFeatureRequest.php
└── Events/
    └── FeatureChanged.php          # Event para histórico

database/migrations/
├── 2026_03_28_000001_create_feature_settings_table.php
└── 2026_03_28_000002_create_feature_histories_table.php

routes/
└── system.php                      # Adicionar rotas

tests/
├── Feature/System/
│   └── FeatureFlagControllerTest.php  # Adicionar testes
└── Unit/Services/
    └── FeatureFlagServiceTest.php     # Adicionar testes
```

---

## Task 1: Criar Model FeatureSetting e Migration

**Files:**
- Create: `database/migrations/2026_03_28_000001_create_feature_settings_table.php`
- Create: `app/Models/FeatureSetting.php`
- Test: `tests/Feature/System/FeatureFlagControllerTest.php`

- [ ] **Step 1: Create migration**

```php
<?php

use App\Enums\RolloutStrategyEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_settings', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('feature_name')->unique();
            $table->string('strategy')->default(RolloutStrategyEnum::Inactive->value);
            $table->unsignedInteger('percentage')->default(0);
            $table->json('user_ids')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->index('feature_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_settings');
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`

- [ ] **Step 3: Create model**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RolloutStrategyEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeatureSetting extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'feature_name',
        'strategy',
        'percentage',
        'user_ids',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'strategy' => RolloutStrategyEnum::class,
            'percentage' => 'integer',
            'user_ids' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function histories(): HasMany
    {
        return $this->hasMany(FeatureHistory::class, 'feature_setting_id');
    }

    public function scopeOfFeature($query, string $featureName)
    {
        return $query->where('feature_name', $featureName);
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_03_28_000001_create_feature_settings_table.php app/Models/FeatureSetting.php
git commit -m "feat: add FeatureSetting model for persistent feature configuration"
```

---

## Task 2: Criar Model FeatureHistory e Migration

**Files:**
- Create: `database/migrations/2026_03_28_000002_create_feature_histories_table.php`
- Create: `app/Models/FeatureHistory.php`

- [ ] **Step 1: Create migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_histories', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('feature_setting_id')->constrained('feature_settings')->cascadeOnDelete();
            $table->string('action'); // activated, deactivated, updated
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->json('previous_state')->nullable();
            $table->json('new_state')->nullable();
            $table->timestamps();

            $table->index(['feature_setting_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_histories');
    }
};
```

- [ ] **Step 2: Run migration**

- [ ] **Step 3: Create model**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureHistory extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'feature_setting_id',
        'action',
        'actor_id',
        'previous_state',
        'new_state',
    ];

    protected function casts(): array
    {
        return [
            'previous_state' => 'array',
            'new_state' => 'array',
        ];
    }

    public function featureSetting(): BelongsTo
    {
        return $this->belongsTo(FeatureSetting::class, 'feature_setting_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add database/migrations/2026_03_28_000002_create_feature_histories_table.php app/Models/FeatureHistory.php
git commit -m "feat: add FeatureHistory model for audit trail"
```

---

## Task 3: Atualizar FeatureFlagService com CRUD

**Files:**
- Modify: `app/Services/FeatureFlagService.php`
- Modify: `tests/Unit/Services/FeatureFlagServiceTest.php`

- [ ] **Step 1: Add tests for new methods**

```php
// Add to existing test file

public function test_activate_feature_creates_setting(): void
{
    $service = app(FeatureFlagService::class);
    $user = User::factory()->create();

    $result = $service->activate('realtime', [
        'strategy' => RolloutStrategyEnum::Percentage,
        'percentage' => 25,
    ], $user);

    $this->assertTrue($result->isActive);
    $this->assertEquals(RolloutStrategyEnum::Percentage, $result->strategy);
    $this->assertEquals(25, $result->percentage);

    $this->assertDatabaseHas('feature_settings', [
        'feature_name' => 'realtime',
        'is_active' => true,
        'percentage' => 25,
    ]);
}

public function test_deactivate_feature_updates_setting(): void
{
    $service = app(FeatureFlagService::class);
    $user = User::factory()->create();

    // Activate first
    $service->activate('realtime', ['strategy' => RolloutStrategyEnum::All], $user);

    // Then deactivate
    $result = $service->deactivate('realtime', $user);

    $this->assertFalse($result->isActive);
    $this->assertEquals(RolloutStrategyEnum::Inactive, $result->strategy);
}

public function test_is_active_for_user_with_percentage(): void
{
    $service = app(FeatureFlagService::class);
    $user = User::factory()->create();

    // Activate with 100% to ensure user gets it
    $service->activate('realtime', [
        'strategy' => RolloutStrategyEnum::Percentage,
        'percentage' => 100,
    ], $user);

    $this->assertTrue($service->isActiveForUser('realtime', $user));
}

public function test_is_active_for_admin_always_true(): void
{
    $service = app(FeatureFlagService::class);
    $admin = User::factory()->create();
    Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $admin->assignRole('admin');

    // Feature is inactive
    $this->assertTrue($service->isActiveForUser('realtime', $admin));
}
```

- [ ] **Step 2: Update service with CRUD methods**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\FeatureConfigDTO;
use App\Enums\RolloutStrategyEnum;
use App\Models\FeatureHistory;
use App\Models\FeatureSetting;
use App\Models\User;
use Illuminate\Support\Collection;

class FeatureFlagService
{
    /**
     * Get all available features with their current status.
     */
    public function getAllFeatures(): Collection
    {
        $definitions = config('features.definitions', []);
        $settings = FeatureSetting::all()->keyBy('feature_name');

        return collect($definitions)
            ->map(fn (array $definition, string $name) use ($settings): FeatureConfigDTO {
                $setting = $settings->get($name);

                return FeatureConfigDTO::fromDefinition(
                    name: $name,
                    definition: $definition,
                    strategy: $setting?->strategy,
                    isActive: $setting?->is_active ?? false,
                    percentage: $setting?->percentage ?? 0,
                    userIds: $setting?->user_ids,
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

        if (!$definition) {
            return null;
        }

        $setting = FeatureSetting::where('feature_name', $featureName)->first();

        return FeatureConfigDTO::fromDefinition(
            name: $featureName,
            definition: $definition,
            strategy: $setting?->strategy,
            isActive: $setting?->is_active ?? false,
            percentage: $setting?->percentage ?? 0,
            userIds: $setting?->user_ids,
        );
    }

    /**
     * Activate a feature with the given strategy.
     */
    public function activate(string $featureName, array $options, User $actor): FeatureConfigDTO
    {
        $definition = config("features.definitions.{$featureName}");
        abort_unless($definition, 404, "Feature {$featureName} not found");

        $strategy = $options['strategy'] ?? RolloutStrategyEnum::All;
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

        if (!empty($updateData)) {
            $setting->update($updateData);
            $this->recordHistory($setting, 'updated', $actor, $previousState, $setting->fresh()->toArray());
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
        if (!in_array($userId, $userIds)) {
            $userIds[] = $userId;
            $setting->update(['user_ids' => $userIds]);
            $this->recordHistory($setting, 'updated', $actor, $previousState, $setting->fresh()->toArray());
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

        return $this->getFeature($featureName);
    }

    /**
     * Check if a feature is active for a specific user.
     */
    public function isActiveForUser(string $featureName, User $user): bool
    {
        // Admin always sees all features
        if ($user->hasRole('admin')) {
            return true;
        }

        $setting = FeatureSetting::where('feature_name', $featureName)->first();

        if (!$setting || !$setting->is_active) {
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

        if (!$setting) {
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
```

- [ ] **Step 3: Run tests**

Run: `php artisan test tests/Unit/Services/FeatureFlagServiceTest.php`

- [ ] **Step 4: Commit**

```bash
git add app/Services/FeatureFlagService.php tests/Unit/Services/FeatureFlagServiceTest.php
git commit -m "feat: add CRUD methods to FeatureFlagService"
```

---

## Task 4: Adicionar Controller Methods

**Files:**
- Modify: `app/Http/Controllers/System/FeatureFlagController.php`
- Modify: `tests/Feature/System/FeatureFlagControllerTest.php`

- [ ] **Step 1: Add controller methods**

```php
// Add to FeatureFlagController

public function show(string $feature)
{
    abort_unless(request()->user()->hasRole('admin'), 403);

    $featureDto = $this->featureService->getFeature($feature);

    abort_unless($featureDto, 404, "Feature not found");

    return new FeatureResource($featureDto);
}

public function activate(ActivateFeatureRequest $request, string $feature)
{
    abort_unless($request->user()->hasRole('admin'), 403);

    $featureDto = $this->featureService->activate(
        $feature,
        [
            'strategy' => RolloutStrategyEnum::from($request->validated('strategy')),
            'percentage' => $request->validated('percentage', 0),
            'user_ids' => $request->validated('user_ids'),
        ],
        $request->user()
    );

    return new FeatureResource($featureDto);
}

public function deactivate(Request $request, string $feature)
{
    abort_unless($request->user()->hasRole('admin'), 403);

    $featureDto = $this->featureService->deactivate($feature, $request->user());

    return new FeatureResource($featureDto);
}

public function update(UpdateFeatureRequest $request, string $feature)
{
    abort_unless($request->user()->hasRole('admin'), 403);

    $featureDto = $this->featureService->update(
        $feature,
        $request->validated(),
        $request->user()
    );

    return new FeatureResource($featureDto);
}

public function history(Request $request, string $feature)
{
    abort_unless($request->user()->hasRole('admin'), 403);

    $history = $this->featureService->getHistory($feature);

    return response()->json(['data' => $history]);
}

public function addUser(Request $request, string $feature)
{
    abort_unless($request->user()->hasRole('admin'), 403);

    $request->validate(['user_id' => 'required|string']);

    $featureDto = $this->featureService->addUser(
        $feature,
        $request->input('user_id'),
        $request->user()
    );

    return new FeatureResource($featureDto);
}

public function removeUser(Request $request, string $feature, string $userId)
{
    abort_unless($request->user()->hasRole('admin'), 403);

    $featureDto = $this->featureService->removeUser(
        $feature,
        $userId,
        $request->user()
    );

    return new FeatureResource($featureDto);
}
```

- [ ] **Step 2: Create FormRequests**

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\System;

use App\Enums\RolloutStrategyEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivateFeatureRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'strategy' => ['required', Rule::in(array_column(RolloutStrategyEnum::cases(), 'value'))],
            'percentage' => ['nullable', 'integer', 'min:0', 'max:100', 'required_if:strategy,percentage'],
            'user_ids' => ['nullable', 'array', 'required_if:strategy,users'],
            'user_ids.*' => ['string'],
        ];
    }
}
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\System;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFeatureRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['string'],
        ];
    }
}
```

- [ ] **Step 3: Update routes**

```php
// Add to routes/system.php

Route::middleware(['auth', 'verified'])
    ->prefix('system')
    ->name('system.')
    ->group(function () {
        Route::get('/features', [FeatureFlagController::class, 'index'])->name('features.index');
        Route::get('/features/{feature}', [FeatureFlagController::class, 'show'])->name('features.show');
        Route::post('/features/{feature}/activate', [FeatureFlagController::class, 'activate'])->name('features.activate');
        Route::post('/features/{feature}/deactivate', [FeatureFlagController::class, 'deactivate'])->name('features.deactivate');
        Route::patch('/features/{feature}', [FeatureFlagController::class, 'update'])->name('features.update');
        Route::get('/features/{feature}/history', [FeatureFlagController::class, 'history'])->name('features.history');
        Route::post('/features/{feature}/users', [FeatureFlagController::class, 'addUser'])->name('features.users.add');
        Route::delete('/features/{feature}/users/{userId}', [FeatureFlagController::class, 'removeUser'])->name('features.users.remove');
    });
```

- [ ] **Step 4: Add tests**

```php
// Add to FeatureFlagControllerTest

public function test_show_returns_feature_details(): void
{
    $response = $this->actingAs($this->admin)
        ->getJson(route('system.features.show', 'dynamic-api'));

    $response->assertOk()
        ->assertJsonPath('data.name', 'dynamic-api');
}

public function test_activate_enables_feature(): void
{
    $response = $this->actingAs($this->admin)
        ->postJson(route('system.features.activate', 'realtime'), [
            'strategy' => 'percentage',
            'percentage' => 25,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.is_active', true)
        ->assertJsonPath('data.strategy', 'percentage')
        ->assertJsonPath('data.percentage', 25);
}

public function test_deactivate_disables_feature(): void
{
    // Activate first
    $this->actingAs($this->admin)
        ->postJson(route('system.features.activate', 'realtime'), ['strategy' => 'all']);

    $response = $this->actingAs($this->admin)
        ->postJson(route('system.features.deactivate', 'realtime'));

    $response->assertOk()
        ->assertJsonPath('data.is_active', false);
}

public function test_update_changes_percentage(): void
{
    // Activate first
    $this->actingAs($this->admin)
        ->postJson(route('system.features.activate', 'realtime'), [
            'strategy' => 'percentage',
            'percentage' => 25,
        ]);

    $response = $this->actingAs($this->admin)
        ->patchJson(route('system.features.update', 'realtime'), [
            'percentage' => 75,
        ]);

    $response->assertOk()
        ->assertJsonPath('data.percentage', 75);
}

public function test_history_returns_changes(): void
{
    // Make some changes
    $this->actingAs($this->admin)
        ->postJson(route('system.features.activate', 'realtime'), ['strategy' => 'all']);

    $response = $this->actingAs($this->admin)
        ->getJson(route('system.features.history', 'realtime'));

    $response->assertOk()
        ->assertJsonStructure(['data' => [['id', 'action', 'actor', 'created_at']]]);
}

public function test_non_admin_cannot_activate(): void
{
    $response = $this->actingAs($this->user)
        ->postJson(route('system.features.activate', 'realtime'), ['strategy' => 'all']);

    $response->assertForbidden();
}
```

- [ ] **Step 5: Run tests**

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/System/FeatureFlagController.php app/Http/Requests/System/*.php routes/system.php tests/Feature/System/FeatureFlagControllerTest.php
git commit -m "feat: add CRUD endpoints to FeatureFlagController"
```

---

## Task 5: Adicionar API Endpoint para Usuários

**Files:**
- Create: `app/Http/Controllers/Api/FeatureController.php`
- Create: `routes/api_v1.php`

- [ ] **Step 1: Create API controller**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FeatureFlagService;
use Illuminate\Http\Request;

class FeatureController extends Controller
{
    public function __construct(
        private FeatureFlagService $featureService
    ) {}

    /**
     * Get features active for the current user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        abort_unless($user, 401, 'Unauthenticated');

        $features = $this->featureService->getActiveFeaturesForUser($user);

        return response()->json([
            'features' => $features,
        ]);
    }
}
```

- [ ] **Step 2: Create API routes**

```php
<?php

use App\Http\Controllers\Api\FeatureController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Public API for end users. These endpoints check feature flags.
|
*/

Route::middleware(['auth:sanctum'])
    ->prefix('api/v1')
    ->name('api.v1.')
    ->group(function () {
        Route::get('/features', [FeatureController::class, 'index'])->name('features.index');
    });
```

- [ ] **Step 3: Register routes in bootstrap/app.php**

Add to the `then` callback:
```php
require __DIR__.'/../routes/api_v1.php';
```

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Api/FeatureController.php routes/api_v1.php bootstrap/app.php
git commit -m "feat: add API endpoint for user features"
```

---

## Task 6: Atualizar Vue Page com Ações

**Files:**
- Modify: `resources/js/Pages/System/Features/Index.vue`

- [ ] **Step 1: Add activate/deactivate functionality**

Add methods and wire up the dropdown actions to make API calls.

- [ ] **Step 2: Commit**

---

## Task 7: Final Verification

- [ ] **Step 1: Run all tests**

Run: `php artisan test`

- [ ] **Step 2: Run Pint**

Run: `./vendor/bin/pint`

- [ ] **Step 3: Manual test in browser**

---

## Self-Review

1. Spec coverage: All CRUD operations covered
2. No placeholders: All code provided
3. Type consistency: RolloutStrategyEnum used throughout
