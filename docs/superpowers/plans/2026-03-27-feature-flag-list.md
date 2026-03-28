# Feature Flag Manager - Story 1: Listar Features Disponíveis

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementar o endpoint GET `/system/projects/{id}/features` para listar todas as features disponíveis com status e estratégia de rollout.

**Architecture:** Seguindo a arquitetura do CLAUDE.md: Controller -> Service -> Model -> Resource. Features são definidas no config/pennant.php e armazenadas por projeto em `project_features` table. Rollout strategies são gerenciadas via Enum.

**Tech Stack:** Laravel 12, PHP 8.4, PostgreSQL, Pennant, Spatie Permission, Inertia.js + Vue 3

---

## Files Structure

```
app/
├── Enums/
│   └── RolloutStrategyEnum.php          # Enum para estratégias de rollout
├── Models/
│   ├── Project.php                       # Model de projeto (nova)
│   └── ProjectFeature.php                # Configuração de features por projeto
├── DTOs/
│   └── FeatureConfigDTO.php              # DTO para transferência de dados
├── Services/
│   └── FeatureFlagService.php            # Lógica de negócio de features
├── Http/
│   ├── Controllers/System/
│   │   └── FeatureFlagController.php     # Controller para gerenciar features
│   ├── Requests/System/
│   │   └── FeatureRequest.php            # FormRequest para validação
│   ├── Middleware/
│   │   └── EnsureFeatureIsEnabled.php    # Middleware para bloquear features
│   └── Resources/
│       └── FeatureResource.php           # Transformação JSON
├── Policies/
│   └── ProjectFeaturePolicy.php          # Autorização

config/
└── features.php                          # Definição das features disponíveis

database/migrations/
├── 2026_03_27_000001_create_projects_table.php
└── 2026_03_27_000002_create_project_features_table.php

routes/
└── system.php                            # Rotas do sistema (nova)

tests/
├── Unit/Enums/
│   └── RolloutStrategyEnumTest.php
├── Unit/Services/
│   └── FeatureFlagServiceTest.php
└── Feature/System/
    └── FeatureFlagControllerTest.php

resources/js/
├── Pages/System/Features/
│   └── Index.vue                         # Página de listagem
└── types/
    └── feature.d.ts                      # TypeScript types
```

---

## Task 1: Criar Enum RolloutStrategyEnum

**Files:**
- Create: `app/Enums/RolloutStrategyEnum.php`
- Test: `tests/Unit/Enums/RolloutStrategyEnumTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Enums;

use App\Enums\RolloutStrategyEnum;
use PHPUnit\Framework\TestCase;

class RolloutStrategyEnumTest extends TestCase
{
    public function test_has_all_required_cases(): void
    {
        $cases = RolloutStrategyEnum::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(RolloutStrategyEnum::Inactive, $cases);
        $this->assertContains(RolloutStrategyEnum::Percentage, $cases);
        $this->assertContains(RolloutStrategyEnum::Users, $cases);
        $this->assertContains(RolloutStrategyEnum::All, $cases);
    }

    public function test_labels_are_correct(): void
    {
        $this->assertEquals('Inativo', RolloutStrategyEnum::Inactive->label());
        $this->assertEquals('Percentual', RolloutStrategyEnum::Percentage->label());
        $this->assertEquals('Usuários Específicos', RolloutStrategyEnum::Users->label());
        $this->assertEquals('Todos', RolloutStrategyEnum::All->label());
    }

    public function test_is_active_returns_correct_boolean(): void
    {
        $this->assertFalse(RolloutStrategyEnum::Inactive->isActive());
        $this->assertTrue(RolloutStrategyEnum::Percentage->isActive());
        $this->assertTrue(RolloutStrategyEnum::Users->isActive());
        $this->assertTrue(RolloutStrategyEnum::All->isActive());
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Enums/RolloutStrategyEnumTest.php`
Expected: FAIL with "Class App\Enums\RolloutStrategyEnum not found"

- [ ] **Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum RolloutStrategyEnum: string
{
    case Inactive = 'inactive';
    case Percentage = 'percentage';
    case Users = 'users';
    case All = 'all';

    public function label(): string
    {
        return match ($this) {
            self::Inactive => 'Inativo',
            self::Percentage => 'Percentual',
            self::Users => 'Usuários Específicos',
            self::All => 'Todos',
        };
    }

    public function isActive(): bool
    {
        return $this !== self::Inactive;
    }
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `php artisan test tests/Unit/Enums/RolloutStrategyEnumTest.php`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add app/Enums/RolloutStrategyEnum.php tests/Unit/Enums/RolloutStrategyEnumTest.php
git commit -m "feat: add RolloutStrategyEnum for feature flag strategies"
```

---

## Task 2: Criar Model Project

**Files:**
- Create: `app/Models/Project.php`
- Create: `database/migrations/2026_03_27_000001_create_projects_table.php`
- Test: `tests/Feature/System/FeatureFlagControllerTest.php` (will use this model)

- [ ] **Step 1: Create migration for projects table**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_projects', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignUuid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['owner_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_projects');
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`
Expected: Migration runs successfully

- [ ] **Step 3: Create Project model**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'owner_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function features(): HasMany
    {
        return $this->hasMany(ProjectFeature::class, 'project_id');
    }

    public function scopeOfOwner($query, string $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Models/Project.php database/migrations/2026_03_27_000001_create_projects_table.php
git commit -m "feat: add Project model with migration"
```

---

## Task 3: Criar Model ProjectFeature

**Files:**
- Create: `app/Models/ProjectFeature.php`
- Create: `database/migrations/2026_03_27_000002_create_project_features_table.php`

- [ ] **Step 1: Create migration for project_features table**

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
        Schema::create('project_features', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->foreignUuid('project_id')->constrained('system_projects')->cascadeOnDelete();
            $table->string('feature_name');
            $table->string('strategy')->default(RolloutStrategyEnum::Inactive->value);
            $table->unsignedInteger('percentage')->default(0);
            $table->json('user_ids')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['project_id', 'feature_name']);
            $table->index(['project_id', 'feature_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_features');
    }
};
```

- [ ] **Step 2: Run migration**

Run: `php artisan migrate`
Expected: Migration runs successfully

- [ ] **Step 3: Create ProjectFeature model**

```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RolloutStrategyEnum;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectFeature extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'project_id',
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function scopeOfProject($query, string $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
```

- [ ] **Step 4: Commit**

```bash
git add app/Models/ProjectFeature.php database/migrations/2026_03_27_000002_create_project_features_table.php
git commit -m "feat: add ProjectFeature model for feature flag storage"
```

---

## Task 4: Criar Config de Features

**Files:**
- Create: `config/features.php`

- [ ] **Step 1: Create features config file**

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Feature Flags Definitions
    |--------------------------------------------------------------------------
    |
    | Here you can define all available feature flags for your projects.
    | Each feature has a name (display), description, and default value.
    |
    */

    'definitions' => [
        'dynamic-api' => [
            'name' => 'Dynamic REST API',
            'description' => 'API REST auto-gerada a partir do schema do banco de dados',
            'default' => false,
        ],
        'realtime' => [
            'name' => 'Realtime Subscriptions',
            'description' => 'Websockets com LISTEN/NOTIFY do PostgreSQL',
            'default' => false,
        ],
        'storage' => [
            'name' => 'File Storage',
            'description' => 'MinIO com buckets e políticas de acesso',
            'default' => false,
        ],
        'otp-auth' => [
            'name' => 'OTP Authentication',
            'description' => 'Login sem senha via código de única vez',
            'default' => false,
        ],
        'database-encryption' => [
            'name' => 'Database Encryption',
            'description' => 'Criptografia de dados sensíveis com pgcrypto',
            'default' => false,
        ],
        'automated-backups' => [
            'name' => 'Automated Backups',
            'description' => 'Backups automáticos programados com retenção',
            'default' => false,
        ],
        'rls' => [
            'name' => 'Row Level Security',
            'description' => 'Isolamento de dados por linha no PostgreSQL',
            'default' => false,
        ],
        'advanced-rbac' => [
            'name' => 'Advanced RBAC',
            'description' => 'Controle de acesso granular com permissões customizadas',
            'default' => false,
        ],
    ],
];
```

- [ ] **Step 2: Commit**

```bash
git add config/features.php
git commit -m "feat: add features configuration file with all feature definitions"
```

---

## Task 5: Criar DTO FeatureConfigDTO

**Files:**
- Create: `app/DTOs/FeatureConfigDTO.php`

- [ ] **Step 1: Create FeatureConfigDTO**

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\RolloutStrategyEnum;
use Illuminate\Support\Collection;

readonly class FeatureConfigDTO
{
    public function __construct(
        public string $name,
        public string $displayName,
        public string $description,
        public bool $isActive,
        public RolloutStrategyEnum $strategy,
        public int $percentage = 0,
        public ?Collection $userIds = null,
    ) {}

    public static function fromDefinition(
        string $name,
        array $definition,
        ?RolloutStrategyEnum $strategy = null,
        bool $isActive = false,
        int $percentage = 0,
        ?array $userIds = null
    ): self {
        return new self(
            name: $name,
            displayName: $definition['name'],
            description: $definition['description'],
            isActive: $isActive,
            strategy: $strategy ?? RolloutStrategyEnum::Inactive,
            percentage: $percentage,
            userIds: $userIds ? collect($userIds) : null,
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'display_name' => $this->displayName,
            'description' => $this->description,
            'is_active' => $this->isActive,
            'strategy' => $this->strategy->value,
            'strategy_label' => $this->strategy->label(),
            'percentage' => $this->percentage,
        ];
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/DTOs/FeatureConfigDTO.php
git commit -m "feat: add FeatureConfigDTO for feature data transfer"
```

---

## Task 6: Criar FeatureFlagService

**Files:**
- Create: `app/Services/FeatureFlagService.php`
- Test: `tests/Unit/Services/FeatureFlagServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Unit\Services;

use App\DTOs\FeatureConfigDTO;
use App\Enums\RolloutStrategyEnum;
use App\Models\Project;
use App\Models\ProjectFeature;
use App\Services\FeatureFlagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FeatureFlagServiceTest extends TestCase
{
    use RefreshDatabase;

    private FeatureFlagService $service;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FeatureFlagService::class);
        $this->project = Project::factory()->create();
    }

    public function test_get_all_features_returns_all_defined_features(): void
    {
        $features = $this->service->getAllFeatures($this->project);

        $this->assertCount(8, $features);
        $this->assertContainsOnlyInstancesOf(FeatureConfigDTO::class, $features);
    }

    public function test_get_all_features_includes_active_status(): void
    {
        ProjectFeature::create([
            'project_id' => $this->project->id,
            'feature_name' => 'dynamic-api',
            'strategy' => RolloutStrategyEnum::All->value,
            'is_active' => true,
        ]);

        $features = $this->service->getAllFeatures($this->project);
        $dynamicApi = $features->first(fn ($f) => $f->name === 'dynamic-api');

        $this->assertTrue($dynamicApi->isActive);
        $this->assertEquals(RolloutStrategyEnum::All, $dynamicApi->strategy);
    }

    public function test_get_all_features_defaults_to_inactive(): void
    {
        $features = $this->service->getAllFeatures($this->project);
        $realtime = $features->first(fn ($f) => $f->name === 'realtime');

        $this->assertFalse($realtime->isActive);
        $this->assertEquals(RolloutStrategyEnum::Inactive, $realtime->strategy);
    }

    public function test_get_all_features_includes_percentage(): void
    {
        ProjectFeature::create([
            'project_id' => $this->project->id,
            'feature_name' => 'realtime',
            'strategy' => RolloutStrategyEnum::Percentage->value,
            'percentage' => 25,
            'is_active' => true,
        ]);

        $features = $this->service->getAllFeatures($this->project);
        $realtime = $features->first(fn ($f) => $f->name === 'realtime');

        $this->assertEquals(25, $realtime->percentage);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Unit/Services/FeatureFlagServiceTest.php`
Expected: FAIL with "Class App\Services\FeatureFlagService not found"

- [ ] **Step 3: Create Project Factory**

Run: `php artisan make:factory ProjectFactory`

```php
<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        $name = $this->faker->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(),
            'owner_id' => User::factory(),
        ];
    }
}
```

- [ ] **Step 4: Add HasFactory trait to User model (if not present)**

Add to `app/Models/User.php`:
```php
use Illuminate\Database\Eloquent\Factories\HasFactory;
// Ensure HasFactory is in the class uses
```

Create `database/factories/UserFactory.php` if needed:
```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }
}
```

- [ ] **Step 5: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\FeatureConfigDTO;
use App\Enums\RolloutStrategyEnum;
use App\Models\Project;
use App\Models\ProjectFeature;
use Illuminate\Support\Collection;

class FeatureFlagService
{
    /**
     * Get all available features for a project with their current status.
     *
     * @return Collection<int, FeatureConfigDTO>
     */
    public function getAllFeatures(Project $project): Collection
    {
        $definitions = config('features.definitions', []);
        $projectFeatures = $project->features()
            ->get()
            ->keyBy('feature_name');

        return collect($definitions)
            ->map(function (array $definition, string $name) use ($projectFeatures): FeatureConfigDTO {
                $projectFeature = $projectFeatures->get($name);

                return FeatureConfigDTO::fromDefinition(
                    name: $name,
                    definition: $definition,
                    strategy: $projectFeature?->strategy,
                    isActive: $projectFeature?->is_active ?? false,
                    percentage: $projectFeature?->percentage ?? 0,
                    userIds: $projectFeature?->user_ids,
                );
            })
            ->values();
    }

    /**
     * Get a single feature's configuration.
     */
    public function getFeature(Project $project, string $featureName): ?FeatureConfigDTO
    {
        $definition = config("features.definitions.{$featureName}");

        if (!$definition) {
            return null;
        }

        $projectFeature = $project->features()
            ->where('feature_name', $featureName)
            ->first();

        return FeatureConfigDTO::fromDefinition(
            name: $featureName,
            definition: $definition,
            strategy: $projectFeature?->strategy,
            isActive: $projectFeature?->is_active ?? false,
            percentage: $projectFeature?->percentage ?? 0,
            userIds: $projectFeature?->user_ids,
        );
    }
}
```

- [ ] **Step 6: Run test to verify it passes**

Run: `php artisan test tests/Unit/Services/FeatureFlagServiceTest.php`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add app/Services/FeatureFlagService.php tests/Unit/Services/FeatureFlagServiceTest.php database/factories/ProjectFactory.php database/factories/UserFactory.php
git commit -m "feat: add FeatureFlagService for feature management logic"
```

---

## Task 7: Criar FeatureResource

**Files:**
- Create: `app/Http/Resources/FeatureResource.php`

- [ ] **Step 1: Create FeatureResource**

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\FeatureConfigDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FeatureResource extends JsonResource
{
    public function __construct(
        private FeatureConfigDTO $feature
    ) {
        parent::__construct($feature);
    }

    public function toArray(Request $request): array
    {
        return [
            'name' => $this->feature->name,
            'display_name' => $this->feature->displayName,
            'description' => $this->feature->description,
            'is_active' => $this->feature->isActive,
            'strategy' => $this->feature->strategy->value,
            'strategy_label' => $this->feature->strategy->label(),
            'percentage' => $this->feature->percentage,
        ];
    }
}
```

- [ ] **Step 2: Create FeatureCollectionResource**

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\DTOs\FeatureConfigDTO;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FeatureCollection extends ResourceCollection
{
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection->map(fn (FeatureConfigDTO $feature) => [
                'name' => $feature->name,
                'display_name' => $feature->displayName,
                'description' => $feature->description,
                'is_active' => $feature->isActive,
                'strategy' => $feature->strategy->value,
                'strategy_label' => $feature->strategy->label(),
                'percentage' => $feature->percentage,
            ]),
        ];
    }
}
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Resources/FeatureResource.php app/Http/Resources/FeatureCollection.php
git commit -m "feat: add FeatureResource and FeatureCollection for JSON transformation"
```

---

## Task 8: Criar ProjectFeaturePolicy

**Files:**
- Create: `app/Policies/ProjectFeaturePolicy.php`

- [ ] **Step 1: Create ProjectFeaturePolicy**

```php
<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectFeaturePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view features for a project.
     */
    public function viewAny(User $user, Project $project): bool
    {
        // Allow if user is the project owner
        if ($user->id === $project->owner_id) {
            return true;
        }

        // Allow if user has admin role
        if ($user->hasRole('admin')) {
            return true;
        }

        // Allow if user has permission to manage features
        return $user->hasPermissionTo('features.manage');
    }

    /**
     * Determine whether the user can view a specific feature.
     */
    public function view(User $user, Project $project): bool
    {
        return $this->viewAny($user, $project);
    }

    /**
     * Determine whether the user can activate features.
     */
    public function activate(User $user, Project $project): bool
    {
        return $this->viewAny($user, $project);
    }

    /**
     * Determine whether the user can deactivate features.
     */
    public function deactivate(User $user, Project $project): bool
    {
        return $this->viewAny($user, $project);
    }
}
```

- [ ] **Step 2: Commit**

```bash
git add app/Policies/ProjectFeaturePolicy.php
git commit -m "feat: add ProjectFeaturePolicy for authorization"
```

---

## Task 9: Criar FeatureFlagController

**Files:**
- Create: `app/Http/Controllers/System/FeatureFlagController.php`
- Test: `tests/Feature/System/FeatureFlagControllerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Tests\Feature\System;

use App\Enums\RolloutStrategyEnum;
use App\Models\Project;
use App\Models\ProjectFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class FeatureFlagControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->project = Project::factory()->create(['owner_id' => $this->user->id]);
    }

    public function test_index_returns_all_features(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('system.projects.features.index', $this->project));

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'display_name',
                        'description',
                        'is_active',
                        'strategy',
                        'strategy_label',
                        'percentage',
                    ],
                ],
            ]);

        $this->assertCount(8, $response->json('data'));
    }

    public function test_index_includes_active_features(): void
    {
        ProjectFeature::create([
            'project_id' => $this->project->id,
            'feature_name' => 'dynamic-api',
            'strategy' => RolloutStrategyEnum::Percentage->value,
            'percentage' => 25,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('system.projects.features.index', $this->project));

        $response->assertOk();

        $dynamicApi = collect($response->json('data'))
            ->firstWhere('name', 'dynamic-api');

        $this->assertTrue($dynamicApi['is_active']);
        $this->assertEquals('percentage', $dynamicApi['strategy']);
        $this->assertEquals(25, $dynamicApi['percentage']);
    }

    public function test_index_forbidden_for_non_owner(): void
    {
        $otherUser = User::factory()->create();

        $response = $this->actingAs($otherUser)
            ->getJson(route('system.projects.features.index', $this->project));

        $response->assertForbidden();
    }

    public function test_index_returns_inertia_view_for_web_request(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('system.projects.features.index', $this->project));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('System/Features/Index')
            ->has('features.data', 8)
            ->has('project')
        );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test tests/Feature/System/FeatureFlagControllerTest.php`
Expected: FAIL with "Route not found"

- [ ] **Step 3: Write minimal implementation**

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Http\Resources\FeatureCollection;
use App\Models\Project;
use App\Services\FeatureFlagService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FeatureFlagController extends Controller
{
    public function __construct(
        private FeatureFlagService $featureService
    ) {}

    /**
     * Display a listing of features for a project.
     */
    public function index(Request $request, Project $project)
    {
        $this->authorize('viewAny', $project);

        $features = $this->featureService->getAllFeatures($project);

        if ($request->wantsJson()) {
            return new FeatureCollection($features);
        }

        return Inertia::render('System/Features/Index', [
            'features' => new FeatureCollection($features),
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
            ],
        ]);
    }
}
```

- [ ] **Step 4: Run test to verify it passes (will still fail due to missing routes)**

Run: `php artisan test tests/Feature/System/FeatureFlagControllerTest.php`
Expected: FAIL with "Route not found" - need to create routes first

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/System/FeatureFlagController.php tests/Feature/System/FeatureFlagControllerTest.php
git commit -m "feat: add FeatureFlagController with index method"
```

---

## Task 10: Criar Rotas do Sistema

**Files:**
- Create: `routes/system.php`
- Modify: `bootstrap/app.php` (to register the routes)

- [ ] **Step 1: Create system routes file**

```php
<?php

use App\Http\Controllers\System\FeatureFlagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| System Routes
|--------------------------------------------------------------------------
|
| Routes for the system administration panel (/system/*).
| These routes manage projects, features, and system configuration.
|
*/

Route::middleware(['auth', 'verified'])
    ->prefix('system')
    ->name('system.')
    ->group(function () {

        // Project Feature Flags
        Route::prefix('projects/{project}')
            ->name('projects.')
            ->group(function () {
                Route::get('/features', [FeatureFlagController::class, 'index'])
                    ->name('features.index');
            });
    });
```

- [ ] **Step 2: Register routes in bootstrap/app.php**

Read current `bootstrap/app.php` and add the system routes. For Laravel 12, the structure should be:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        // Add this line:
        then: fn () => require __DIR__.'/../routes/system.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
```

- [ ] **Step 3: Run test to verify it passes**

Run: `php artisan test tests/Feature/System/FeatureFlagControllerTest.php`
Expected: PASS

- [ ] **Step 4: Commit**

```bash
git add routes/system.php bootstrap/app.php
git commit -m "feat: add system routes for feature flag management"
```

---

## Task 11: Criar Página Vue de Listagem

**Files:**
- Create: `resources/js/Pages/System/Features/Index.vue`
- Create: `resources/js/types/feature.d.ts`

- [ ] **Step 1: Create TypeScript types**

```typescript
// resources/js/types/feature.d.ts
export interface Feature {
    name: string;
    display_name: string;
    description: string;
    is_active: boolean;
    strategy: 'inactive' | 'percentage' | 'users' | 'all';
    strategy_label: string;
    percentage: number;
}

export interface FeatureCollection {
    data: Feature[];
}

export interface Project {
    id: string;
    name: string;
    slug: string;
}
```

- [ ] **Step 2: Create Vue page**

```vue
<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { FeatureCollection, Project } from '@/types/feature';
import { MoreHorizontal, Settings, Play, Square } from 'lucide-vue-next';

defineProps<{
    features: FeatureCollection;
    project: Project;
}>();

const getStrategyBadgeVariant = (strategy: string): 'default' | 'secondary' | 'outline' => {
    if (strategy === 'all') return 'default';
    if (strategy === 'inactive') return 'outline';
    return 'secondary';
};
</script>

<template>
    <Head :title="`Features - ${project.name}`" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-foreground">
                        Feature Flags
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ project.name }}
                    </p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-card shadow-sm rounded-lg border border-border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead class="w-[200px]">Feature</TableHead>
                                <TableHead>Descrição</TableHead>
                                <TableHead class="w-[120px]">Status</TableHead>
                                <TableHead class="w-[150px]">Estratégia</TableHead>
                                <TableHead class="w-[100px]">Rollout</TableHead>
                                <TableHead class="w-[80px]"></TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow
                                v-for="feature in features.data"
                                :key="feature.name"
                            >
                                <TableCell class="font-medium">
                                    {{ feature.display_name }}
                                </TableCell>
                                <TableCell class="text-muted-foreground">
                                    {{ feature.description }}
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="feature.is_active ? 'default' : 'outline'"
                                        :class="feature.is_active ? 'bg-green-500/10 text-green-500 hover:bg-green-500/20' : ''"
                                    >
                                        {{ feature.is_active ? 'Ativo' : 'Inativo' }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        :variant="getStrategyBadgeVariant(feature.strategy)"
                                    >
                                        {{ feature.strategy_label }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <span
                                        v-if="feature.strategy === 'percentage'"
                                        class="text-sm"
                                    >
                                        {{ feature.percentage }}%
                                    </span>
                                    <span
                                        v-else-if="feature.strategy === 'all'"
                                        class="text-sm text-green-500"
                                    >
                                        100%
                                    </span>
                                    <span
                                        v-else
                                        class="text-sm text-muted-foreground"
                                    >
                                        -
                                    </span>
                                </TableCell>
                                <TableCell>
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem
                                                v-if="!feature.is_active"
                                            >
                                                <Play class="mr-2 h-4 w-4" />
                                                Ativar
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="feature.is_active"
                                            >
                                                <Square class="mr-2 h-4 w-4" />
                                                Desativar
                                            </DropdownMenuItem>
                                            <DropdownMenuItem>
                                                <Settings class="mr-2 h-4 w-4" />
                                                Configurar
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/System/Features/Index.vue resources/js/types/feature.d.ts
git commit -m "feat: add Features Index page with Vue component"
```

---

## Task 12: Verificar e Rodar Todos os Testes

**Files:**
- All test files created

- [ ] **Step 1: Run all tests**

Run: `php artisan test --parallel`
Expected: All tests PASS

- [ ] **Step 2: Run linting/formatting**

Run: `./vendor/bin/pint`
Expected: No files changed

- [ ] **Step 3: Final commit if any fixes needed**

```bash
git add -A
git commit -m "fix: apply code style fixes"
```

---

## Task 13: Criar Middleware EnsureFeatureIsEnabled

**Files:**
- Create: `app/Http/Middleware/EnsureFeatureIsEnabled.php`

- [ ] **Step 1: Create middleware**

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Pennant\Feature;

class EnsureFeatureIsEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): mixed
    {
        $user = $request->user();

        // System users (admin) bypass feature flags
        if ($user instanceof User && $user->hasRole('admin')) {
            return $next($request);
        }

        // Check if feature is active for the user
        if (!Feature::active($feature)) {
            if ($request->wantsJson()) {
                return new JsonResponse([
                    'error' => 'feature_disabled',
                    'message' => 'This feature is not available for your account',
                ], Response::HTTP_FORBIDDEN);
            }

            abort(Response::HTTP_FORBIDDEN, 'This feature is not available for your account');
        }

        return $next($request);
    }
}
```

- [ ] **Step 2: Register middleware in bootstrap/app.php**

Add to the middleware configuration:
```php
->withMiddleware(function (Middleware $middleware) {
    // ... existing middleware ...

    // Register feature middleware alias
    $middleware->alias([
        'feature' => \App\Http\Middleware\EnsureFeatureIsEnabled::class,
    ]);
})
```

- [ ] **Step 3: Commit**

```bash
git add app/Http/Middleware/EnsureFeatureIsEnabled.php bootstrap/app.php
git commit -m "feat: add EnsureFeatureIsEnabled middleware for feature access control"
```

---

## Self-Review Checklist

### 1. Spec Coverage
| Requirement | Task |
|-------------|------|
| GET `/system/projects/{id}/features` lista features | Task 9, 10 |
| Retorna name, description, status, rollout_strategy | Task 5, 7 |
| Admin do projeto pode ver | Task 8 |
| Features: dynamic-api, realtime, otp-auth, storage | Task 4 |

### 2. Placeholder Scan
- [x] No TBD, TODO, or "implement later"
- [x] No "Add appropriate error handling"
- [x] All code steps have actual code
- [x] No references to undefined types/functions

### 3. Type Consistency
- [x] `RolloutStrategyEnum` used consistently
- [x] `FeatureConfigDTO` properties match Resource output
- [x] `ProjectFeature` model uses correct cast types

---

## Execution Handoff

**Plan complete and saved to `docs/superpowers/plans/2026-03-27-feature-flag-list.md`.**

**Two execution options:**

1. **Subagent-Driven (recommended)** - I dispatch a fresh subagent per task, review between tasks, fast iteration

2. **Inline Execution** - Execute tasks in this session using executing-plans, batch execution with checkpoints

**Which approach?**
