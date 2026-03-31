# Feature Flags Refactor - Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Refatorar feature flags para usar apenas features implementadas, ativar por ambiente, e restringir menu do admin.

**Architecture:** Config reduzido com `implemented_at`, service com lógica de ambiente (dev: todas on, prod: por data), menu admin separado, UI com toggle.

**Tech Stack:** Laravel 13, PHP 8.4, Vue 3, Inertia.js, shadcn-vue Switch

---

## Task 1: Atualizar Config de Features

**Files:**
- Modify: `config/features.php`

- [ ] **Step 1: Substituir config/features.php**

```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | First Deploy Date
    |--------------------------------------------------------------------------
    |
    | Data do primeiro deploy em produção. Features com implemented_at
    | anterior ou igual a esta data são ativadas por padrão em prod.
    | Em dev/local, todas as features implementadas são ativadas.
    |
    */
    'first_deploy_date' => env('FEATURES_FIRST_DEPLOY_DATE'),

    'definitions' => [
        'database-creator' => [
            'name' => 'Database Creator',
            'description' => 'Interface para criar e gerenciar databases PostgreSQL',
            'implemented_at' => '2026-03-15',
        ],
        'credentials-manager' => [
            'name' => 'Credentials Manager',
            'description' => 'Gerenciamento de credenciais de acesso ao sistema',
            'implemented_at' => '2026-03-20',
        ],
    ],
];
```

- [ ] **Step 2: Commit**

```bash
git add config/features.php
git commit -m "refactor(features): reduce to implemented features only with implemented_at"
```

---

## Task 2: Adicionar Variável de Ambiente

**Files:**
- Modify: `.env`
- Modify: `.env.example`

- [ ] **Step 1: Adicionar ao .env**

```env
FEATURES_FIRST_DEPLOY_DATE=2026-03-30
```

- [ ] **Step 2: Adicionar ao .env.example**

```env
FEATURES_FIRST_DEPLOY_DATE=
```

- [ ] **Step 3: Commit**

```bash
git add .env .env.example
git commit -m "chore: add FEATURES_FIRST_DEPLOY_DATE env variable"
```

---

## Task 3: Atualizar FeatureFlagService

**Files:**
- Modify: `app/Services/FeatureFlagService.php`
- Modify: `tests/Unit/Services/FeatureFlagServiceTest.php`

- [ ] **Step 1: Adicionar import Carbon e método isFeatureActiveByDefault**

No topo do arquivo, após os imports existentes, o Carbon já deve estar disponível via Laravel. Adicionar o método `isFeatureActiveByDefault` após o método `getHistory`:

```php
/**
 * Check if a feature is active by default based on environment.
 */
public function isFeatureActiveByDefault(string $featureName): bool
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

    return \Carbon\Carbon::parse($feature['implemented_at'])
        ->lte(\Carbon\Carbon::parse($deployDate));
}
```

- [ ] **Step 2: Modificar isActiveForUser para usar isFeatureActiveByDefault**

Substituir o método `isActiveForUser` existente:

```php
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

    // Se não há setting no banco, usa o default por ambiente
    if (! $setting) {
        return $this->isFeatureActiveByDefault($featureName);
    }

    // Se há setting mas está inativo, feature desativada
    if (! $setting->is_active) {
        return false;
    }

    // Se há setting ativo, segue a estratégia definida
    return match ($setting->strategy) {
        RolloutStrategyEnum::All => true,
        RolloutStrategyEnum::Percentage => $this->checkPercentage($user->id, $setting->percentage),
        RolloutStrategyEnum::Users => in_array($user->id, $setting->user_ids ?? []),
        RolloutStrategyEnum::Inactive => false,
    };
}
```

- [ ] **Step 3: Atualizar teste para refletir novo count de features**

Modificar `tests/Unit/Services/FeatureFlagServiceTest.php`:

```php
public function test_get_all_features_returns_all_defined_features(): void
{
    $features = $this->service->getAllFeatures();

    $this->assertCount(2, $features); // Apenas database-creator e credentials-manager
    $this->assertContainsOnlyInstancesOf(FeatureConfigDTO::class, $features);
}

public function test_get_all_features_defaults_to_inactive(): void
{
    $features = $this->service->getAllFeatures();
    $databaseCreator = $features->first(fn ($f) => $f->name === 'database-creator');

    // Sem setting no banco, estratégia é Inactive
    $this->assertFalse($databaseCreator->isActive);
    $this->assertEquals(RolloutStrategyEnum::Inactive, $databaseCreator->strategy);
}

public function test_get_feature_returns_dto_for_known(): void
{
    $feature = $this->service->getFeature('database-creator');

    $this->assertInstanceOf(FeatureConfigDTO::class, $feature);
    $this->assertEquals('database-creator', $feature->name);
    $this->assertEquals('Database Creator', $feature->displayName);
}
```

- [ ] **Step 4: Adicionar teste para isFeatureActiveByDefault**

Adicionar novos testes no final da classe:

```php
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
```

- [ ] **Step 5: Rodar testes**

```bash
php artisan test tests/Unit/Services/FeatureFlagServiceTest.php
```

Expected: All tests pass

- [ ] **Step 6: Commit**

```bash
git add app/Services/FeatureFlagService.php tests/Unit/Services/FeatureFlagServiceTest.php
git commit -m "feat(features): add environment-based default activation"
```

---

## Task 4: Atualizar FeatureServiceProvider

**Files:**
- Modify: `app/Providers/FeatureServiceProvider.php`

- [ ] **Step 1: Adicionar método isFeatureActiveByDefault e atualizar resolveFeature**

Substituir todo o conteúdo do arquivo:

```php
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
            'percentage' => $this->checkPercentage($user->id, $setting->percentage),
            'users' => in_array($user->id, $setting->user_ids ?? []),
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
```

- [ ] **Step 2: Commit**

```bash
git add app/Providers/FeatureServiceProvider.php
git commit -m "feat(features): add environment-based default in provider"
```

---

## Task 5: Criar Rota e Controller para Users (Admin)

**Files:**
- Modify: `routes/system.php`
- Modify: `app/Http/Controllers/UserController.php`
- Create: `app/Http/Resources/SystemUserCollection.php`
- Create: `app/Http/Resources/SystemUserResource.php`

- [ ] **Step 1: Criar SystemUserResource**

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SystemUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_admin' => $this->is_admin,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}
```

- [ ] **Step 2: Criar SystemUserCollection**

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SystemUserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => SystemUserResource::collection($this->collection),
        ];
    }
}
```

- [ ] **Step 3: Adicionar método indexForAdmin no UserController**

Adicionar ao `app/Http/Controllers/UserController.php`:

```php
use App\Http\Resources\SystemUserCollection;

// ... no final da classe, adicionar:

/**
 * List all users for admin panel.
 */
public function indexForAdmin(Request $request): SystemUserCollection
{
    $users = User::select(['id', 'name', 'email', 'is_admin', 'created_at', 'updated_at'])
        ->orderBy('created_at', 'desc')
        ->get();

    return new SystemUserCollection($users);
}
```

- [ ] **Step 4: Adicionar rota em routes/system.php**

Adicionar após a linha 6 (após o use statement):

```php
use App\Http\Controllers\UserController;
```

Adicionar dentro do grupo de rotas (após a linha 31):

```php
        // Users - God Admin only
        Route::get('/users', [UserController::class, 'indexForAdmin'])->name('users.index');
```

- [ ] **Step 5: Commit**

```bash
git add routes/system.php app/Http/Controllers/UserController.php app/Http/Resources/SystemUserResource.php app/Http/Resources/SystemUserCollection.php
git commit -m "feat(admin): add users list route for admin panel"
```

---

## Task 6: Criar Página de Usuários (Admin)

**Files:**
- Create: `resources/js/Pages/System/Users/Index.vue`
- Create: `resources/js/types/user.ts`

- [ ] **Step 1: Criar types/user.ts**

```typescript
export interface SystemUser {
    id: string;
    name: string;
    email: string;
    is_admin: boolean;
    created_at: string;
    updated_at: string;
}

export interface SystemUserCollection {
    data: SystemUser[];
}
```

- [ ] **Step 2: Criar Pages/System/Users/Index.vue**

```vue
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import type { SystemUserCollection } from '@/types/user';

defineProps<{
    users: SystemUserCollection;
}>();

const formatDate = (date: string): string => {
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    });
};
</script>

<template>
    <Head title="Usuários" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-foreground">
                        Usuários
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Lista de todos os usuários do sistema
                    </p>
                </div>
            </div>
        </template>

        <div class="bg-card shadow-sm rounded-lg border border-border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nome</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead class="w-[120px]">Tipo</TableHead>
                        <TableHead class="w-[140px]">Criado em</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="user in users.data"
                        :key="user.id"
                    >
                        <TableCell class="font-medium">
                            {{ user.name }}
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ user.email }}
                        </TableCell>
                        <TableCell>
                            <Badge
                                v-if="user.is_admin"
                                variant="default"
                                class="bg-primary"
                            >
                                Admin
                            </Badge>
                            <Badge v-else variant="outline">
                                Usuário
                            </Badge>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ formatDate(user.created_at) }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/System/Users/Index.vue resources/js/types/user.ts
git commit -m "feat(admin): add users list page for admin panel"
```

---

## Task 7: Atualizar Menu do Admin

**Files:**
- Modify: `resources/js/Layouts/AuthenticatedLayout.vue`

- [ ] **Step 1: Atualizar imports e adicionar ícone Users**

Adicionar `Users` aos imports do lucide-vue-next (linha 13-24):

```typescript
import {
    Database,
    Home,
    Sun,
    Moon,
    LogOut,
    Settings,
    PanelLeftClose,
    PanelLeft,
    ChevronDown,
    Flag,
    Key,
    Users,
} from 'lucide-vue-next';
```

- [ ] **Step 2: Esconder Databases e Credentials do admin, adicionar Users na seção Sistema**

Substituir a seção de navegação (linhas 100-172) por:

```vue
            <!-- Navigation -->
            <nav class="flex-1 space-y-1 p-2">
                <!-- App Section (hidden for admin) -->
                <template v-if="!auth.user.is_admin">
                    <Link
                        :href="route('dashboard')"
                        :class="[
                            'flex items-center rounded-lg text-sm font-medium transition-colors',
                            collapsed
                                ? 'justify-center p-3'
                                : 'gap-3 px-3 py-2',
                            route().current('dashboard')
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                        ]"
                    >
                        <Home class="h-5 w-5 shrink-0" />
                        <span v-if="!collapsed">Home</span>
                    </Link>

                    <Link
                        :href="route('app.databases.index')"
                        :class="[
                            'flex items-center rounded-lg text-sm font-medium transition-colors',
                            collapsed
                                ? 'justify-center p-3'
                                : 'gap-3 px-3 py-2',
                            route().current('app.databases.*')
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                        ]"
                    >
                        <Database class="h-5 w-5 shrink-0" />
                        <span v-if="!collapsed">Databases</span>
                    </Link>

                    <Link
                        :href="route('app.credentials.index')"
                        :class="[
                            'flex items-center rounded-lg text-sm font-medium transition-colors',
                            collapsed
                                ? 'justify-center p-3'
                                : 'gap-3 px-3 py-2',
                            route().current('app.credentials.*')
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                        ]"
                    >
                        <Key class="h-5 w-5 shrink-0" />
                        <span v-if="!collapsed">Credentials</span>
                    </Link>
                </template>

                <!-- Dashboard for admin (minimal) -->
                <Link
                    v-if="auth.user.is_admin"
                    :href="route('dashboard')"
                    :class="[
                        'flex items-center rounded-lg text-sm font-medium transition-colors',
                        collapsed
                            ? 'justify-center p-3'
                            : 'gap-3 px-3 py-2',
                        route().current('dashboard')
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                    ]"
                >
                    <Home class="h-5 w-5 shrink-0" />
                    <span v-if="!collapsed">Home</span>
                </Link>

                <!-- System Section (Admin Only) -->
                <div v-if="auth.user.is_admin" class="pt-4 mt-4 border-t border-border">
                    <p v-if="!collapsed" class="px-3 mb-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                        Sistema
                    </p>
                    <Link
                        :href="route('system.features.index')"
                        :class="[
                            'flex items-center rounded-lg text-sm font-medium transition-colors',
                            collapsed
                                ? 'justify-center p-3'
                                : 'gap-3 px-3 py-2',
                            route().current('system.features.*')
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                        ]"
                    >
                        <Flag class="h-5 w-5 shrink-0" />
                        <span v-if="!collapsed">Features</span>
                    </Link>
                    <Link
                        :href="route('system.users.index')"
                        :class="[
                            'flex items-center rounded-lg text-sm font-medium transition-colors',
                            collapsed
                                ? 'justify-center p-3'
                                : 'gap-3 px-3 py-2',
                            route().current('system.users.*')
                                ? 'bg-primary text-primary-foreground'
                                : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground',
                        ]"
                    >
                        <Users class="h-5 w-5 shrink-0" />
                        <span v-if="!collapsed">Usuários</span>
                    </Link>
                </div>
            </nav>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Layouts/AuthenticatedLayout.vue
git commit -m "feat(ui): separate admin menu from user menu"
```

---

## Task 8: Substituir Dropdown por Toggle na UI de Features

**Files:**
- Modify: `resources/js/Pages/System/Features/Index.vue`

- [ ] **Step 1: Atualizar imports**

Substituir os imports (linhas 1-22) por:

```vue
<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import { Switch } from '@/components/ui/switch';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { FeatureCollection } from '@/types/feature';

defineProps<{
    features: FeatureCollection;
}>();

const toggling = ref<string | null>(null);

const getCsrfToken = (): string => {
    const meta = document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement;
    return meta?.content || '';
};

const toggleFeature = async (featureName: string, currentlyActive: boolean): Promise<void> => {
    toggling.value = featureName;
    try {
        const url = currentlyActive
            ? route('system.features.deactivate', featureName)
            : route('system.features.activate', featureName);

        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({ strategy: 'all' }),
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        router.reload({ only: ['features'] });
    } catch (error) {
        console.error('Failed to toggle feature:', error);
    } finally {
        toggling.value = null;
    }
};

const getStrategyBadgeVariant = (strategy: string): 'default' | 'secondary' | 'outline' => {
    if (strategy === 'all') return 'default';
    if (strategy === 'inactive') return 'outline';
    return 'secondary';
};
</script>
```

- [ ] **Step 2: Atualizar template da tabela**

Substituir todo o template (de `<template>` em diante):

```vue
<template>
    <Head title="Feature Flags" />

    <AuthenticatedLayout :auth="$page.props.auth">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-semibold text-foreground">
                        Feature Flags
                    </h2>
                    <p class="text-sm text-muted-foreground mt-1">
                        Gerencie as features disponíveis na sua instância
                    </p>
                </div>
            </div>
        </template>

        <div class="bg-card shadow-sm rounded-lg border border-border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-[200px]">Feature</TableHead>
                        <TableHead>Descrição</TableHead>
                        <TableHead class="w-[100px]">Status</TableHead>
                        <TableHead class="w-[150px]">Estratégia</TableHead>
                        <TableHead class="w-[100px]">Rollout</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow
                        v-for="feature in features.data"
                        :key="feature.name"
                    >
                        <TableCell class="font-medium">
                            <Link
                                :href="route('system.features.show', feature.name)"
                                class="hover:underline"
                            >
                                {{ feature.display_name }}
                            </Link>
                        </TableCell>
                        <TableCell class="text-muted-foreground">
                            {{ feature.description }}
                        </TableCell>
                        <TableCell>
                            <Switch
                                :model-value="feature.is_active"
                                :disabled="toggling === feature.name"
                                @update:model-value="toggleFeature(feature.name, feature.is_active)"
                            />
                        </TableCell>
                        <TableCell>
                            <Badge :variant="getStrategyBadgeVariant(feature.strategy)">
                                {{ feature.strategy_label }}
                            </Badge>
                        </TableCell>
                        <TableCell>
                            <span v-if="feature.strategy === 'percentage'" class="text-sm">
                                {{ feature.percentage }}%
                            </span>
                            <span v-else-if="feature.strategy === 'all'" class="text-sm text-green-500">
                                100%
                            </span>
                            <span v-else class="text-sm text-muted-foreground">
                                -
                            </span>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </AuthenticatedLayout>
</template>
```

- [ ] **Step 3: Commit**

```bash
git add resources/js/Pages/System/Features/Index.vue
git commit -m "feat(ui): replace dropdown with toggle switch for feature activation"
```

---

## Task 9: Build e Teste Final

- [ ] **Step 1: Build do frontend**

```bash
npm run build
```

Expected: Build succeeds without errors

- [ ] **Step 2: Rodar todos os testes**

```bash
php artisan test
```

Expected: All tests pass

- [ ] **Step 3: Commit final**

```bash
git add -A
git commit -m "chore: final build and test verification"
```

---

## Resumo

| Task | Descrição |
|------|-----------|
| 1 | Config de features reduzido para 2 features reais |
| 2 | Variável de ambiente FEATURES_FIRST_DEPLOY_DATE |
| 3 | FeatureFlagService com lógica de ambiente |
| 4 | FeatureServiceProvider sincronizado |
| 5 | Rotas e controller para users admin |
| 6 | Página de listagem de usuários |
| 7 | Menu separado admin vs usuário |
| 8 | Toggle switch na UI de features |
| 9 | Build e testes finais |
