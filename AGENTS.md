# AGENTS.md - Instruções para Agentes AI no DockaBase

Este documento orienta agentes AI (Claude, GPT, etc.) que trabalham no código do DockaBase.

## Princípio Fundamental: Use Laravel ao Máximo

**Regra de ouro:** Laravel já tem uma solução. Use-a. Não reinvente a roda.

Antes de escrever código, pergunte: "Laravel tem isso nativamente?"

---

## Laravel 13+ Features - OBRIGATÓRIO

### PHP 8.4+ Syntax

```php
declare(strict_types=1); // OBRIGATÓRIO em todos os arquivos

// Constructor Property Promotion
public function __construct(
    private DatabaseService $service
) {}

// Property Hooks para casts
protected function casts(): array
{
    return [
        'is_active' => 'boolean',
        'settings' => 'array',
    ];
}
```

### PHP Attributes - Use em vez de chamadas de método

```php
// Model - usar attributes para relationships/behavior
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;

#[ObservedBy(DatabaseObserver::class)]
#[ScopedBy(ActiveScope::class)]
class Database extends Model
{
    use \Illuminate\Database\Eloquent\Attributes\HasFactory;
}
```

### Queue Routing - Laravel 13

```php
// routes/api.php - rota direta para queue
use Illuminate\Support\Facades\Queue;

// Dispatch para queue específica
CreateDatabaseJob::dispatch($database)
    ->onQueue('database-creation');

// Ou via attribute no Job
#[Queue('database-creation')]
class CreateDatabaseJob implements ShouldQueue
{
    // ...
}
```

### Laravel Boost - Sempre que aplicável

```bash
# Use boost para operações otimizadas
php artisan boost:optimize
php artisan boost:cache
```

---

## Route Model Binding - OBRIGATÓRIO

### Sempre use implicit binding em controllers

```php
// ✅ CERTO
public function show(Database $database): DatabaseResource
{
    return new DatabaseResource($database);
}

// ❌ ERRADO - NUNCA faça isso
public function show(string $id): DatabaseResource
{
    $database = Database::findOrFail($id);
    return new DatabaseResource($database);
}
```

### Custom keys para KSUID

```php
// Model
public function getRouteKeyName(): string
{
    return 'id'; // KSUID
}

// Route
Route::get('/databases/{database}', DatabaseController::class);

// Controller - Laravel resolve automaticamente
public function show(Database $database): DatabaseResource
{
    // $database já está carregado via KSUID
}
```

### Explicit binding quando necessário

```php
// RouteServiceProvider
public function boot(): void
{
    Route::model('database', Database::class);
    Route::bind('database_by_name', fn (string $name) => Database::where('name', $name)->firstOrFail());
}

// Route
Route::get('/databases/{database_by_name}', DatabaseController::class);
```

---

## Scopes - Convenção `scopeOf{Entity}()`

### Sempre seguir a convenção para filtros

```php
// Model
public function scopeOfStatus($query, string $status)
{
    return $query->where('status', $status);
}

public function scopeOfName($query, string $name)
{
    return $query->where('name', $name);
}

public function scopeActive($query)
{
    return $query->where('is_active', true);
}
```

### Uso em controllers/queries

```php
// ✅ CERTO - use scopes
$databases = Database::active()
    ->ofStatus('ready')
    ->ofName($request->input('name'))
    ->get();

// ❌ ERRADO - não faça queries inline
$databases = Database::where('is_active', true)
    ->where('status', 'ready')
    ->where('name', $request->input('name'))
    ->get();
```

---

## Resources - Transformação JSON

### Sempre use Resources para respostas API

```php
// Resource
class DatabaseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'status' => $this->status,
            'progress' => $this->progress,
            'credentials_count' => $this->whenCounted('credentials'),
            'created_at' => $this->created_at?->diffForHumans(),
        ];
    }
}

// Controller
public function index(): DatabaseCollection
{
    return new DatabaseCollection(Database::all());
}
```

### Conditional output com `when()`

```php
return [
    'id' => $this->id,
    'secret' => $this->when(auth()->user()->isAdmin(), $this->secret),
    'credentials' => DatabaseCredentialResource::collection($this->whenLoaded('credentials')),
];
```

---

## FormRequest - Validação + Autorização

### Sempre valide via FormRequest

```php
// Request
class CreateDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Database::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'alpha_dash', 'max:50', 'unique:databases,name'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'database_name' => ['required', 'string', 'max:50'],
            'credential_ids' => ['nullable', 'array'],
            'credential_ids.*' => ['string', 'size:27', 'exists:credentials,id'],
        ];
    }
}

// Controller - use validated()
public function store(CreateDatabaseRequest $request): DatabaseResource
{
    $database = $this->service->create($request->validated());
    return new DatabaseResource($database);
}
```

---

## Arquitetura de Camadas

### Responsabilidades claras

| Camada | FAZ | NÃO FAZ |
|--------|-----|---------|
| **Controller** | Orquestra: busca dados, chama service, transaciona, dispara eventos | Regras de negócio complexas |
| **Service** | Regras de negócio puras (entrada → processamento → saída) | Busca dados, dispara eventos, transaciona |
| **Model** | Scopes, relationships, property hooks, casts | Lógica de negócio complexa |
| **FormRequest** | Validação + autorização | Buscar dados |
| **Resource** | Transforma Model em JSON | Alterar dados |

### Controller (Orquestração)

```php
public function store(CreateDatabaseRequest $request): DatabaseResource
{
    // 1. Buscar dados adicionais se necessário
    $credentials = Credential::findMany($request->validated('credential_ids', []));

    // 2. Transação
    return DB::transaction(function () use ($request, $credentials) {
        // 3. Chamar service para criar
        $database = $this->service->create(
            $request->validated(),
            $credentials
        );

        // 4. Disparar job/evento
        CreateDatabaseJob::dispatch($database);

        // 5. Retornar resource
        return new DatabaseResource($database);
    });
}
```

### Service (Regras Puras)

```php
public function create(array $data, Collection $credentials): Database
{
    // Validações de negócio
    if ($data['port'] < 1024) {
        throw new InvalidArgumentException('Port must be >= 1024');
    }

    // Criar e retornar
    return Database::create($data);
}

// Service NÃO busca dados, NÃO dispara eventos
```

---

## Convenções de Nomenclatura

### Scopes: `scopeOf{Entity}()`
- `scopeOfStatus($query, $status)`
- `scopeOfName($query, $name)`
- `scopeOfUser($query, $userId)`

### Policies: `{Model}Policy`
- `DatabasePolicy` para `Database` model
- Métodos: `view()`, `create()`, `update()`, `delete()`, `viewAny()`

### Routes
- `/system/*` → Painel administrativo
- `/api/v1/*` → API pública
- `/app/*` → App UI (Inertia)

---

## Padrões Específicos do DockaBase

### Models com KSUID

```php
use App\Traits\HasKsuid;

class Database extends Model
{
    use HasKsuid; // Inclui automaticamente o trait

    // ID é gerado automaticamente
}
```

### Soft Deletes

```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Database extends Model
{
    use SoftDeletes;

    // Queries automticamente excluem deleted
    // Use withTrashed() para incluir
}
```

### Feature Flags

```php
use Illuminate\Support\Facades\Feature;
use App\Features\DatabaseCreator;

// Em controllers
if (Feature::active('database-creator', $user)) {
    // ...
}

// Ou via attribute no método
#[Feature('database-creator')]
public function create(): Response
{
    // ...
}
```

---

## O Que NÃO Fazer

### ❌ Não reescreva funcionalidades do Laravel
```php
// ❌ ERRADO
if (!isset($data['name'])) {
    throw new Exception('Name is required');
}

// ✅ CERTO - use FormRequest validation
'name' => ['required']
```

### ❌ Não faça queries complexas no controller
```php
// ❌ ERRADO
$databases = DB::table('databases')
    ->join('credentials', '...')
    ->where('...')
    ->get();

// ✅ CERTO - use scopes no Model
$databases = Database::active()->ofStatus('ready')->get();
```

### ❌ Não use IDs incrementais para entidades principais
```php
// ❌ ERRADO
public function getUser(int $id): User
{
    return User::find($id);
}

// ✅ CERTO - KSUID + Route Model Binding
public function show(User $user): UserResource
{
    return new UserResource($user);
}
```

### ❌ Não faça autorização manual no controller
```php
// ❌ ERRADO
if ($request->user()->cannot('update', $database)) {
    abort(403);
}

// ✅ CERTO - use Policy ou authorize()
$this->authorize('update', $database);
```

---

## RBAC e Feature Flags - Validação de Exibição

### TODAS as features devem validar exibição em 2 níveis

**Nível 1: Feature Flag (Sidebar)**
- Links na sidebar só aparecem se a feature ESTIVER ATIVA para o usuário
- Usa `activeFeatures?.includes('feature-name')`

**Nível 2: RBAC (Botões e Links)**
- Botões de criar/editar/excluir só aparecem se o usuário TEM A PERMISSÃO
- Usa composable `usePermissions()` → `canCreate()`, `canEdit()`, `canDelete()`

### Implementação Padrão

**1. Sidebar (2 níveis de validação)**
```vue
<!-- Link aparece se: feature está ATIVA E usuário tem VIEW permission -->
<Link
    v-if="!auth.user.is_admin
           && activeFeatures?.includes('database-creator')
           && canView('databases')"
    :href="route('app.databases.index')"
>
    <Database class="h-5 w-5 shrink-0" />
    <span v-if="!collapsed">Databases</span>
</Link>
```

**2. Botão Criar (RBAC)**
```vue
<Button v-if="canCreate('databases')" @click="openCreateDialog">
    <Plus class="h-4 w-4 mr-2" />
    Novo Database
</Button>
```

**3. Dropdown de Ações (RBAC granular)**
```vue
<DropdownMenuContent align="end">
    <DropdownMenuItem as-child>
        <Link :href="route('app.databases.show', database.id)">
            <Eye class="mr-2 h-4 w-4" />
            Visualizar
        </Link>
    </DropdownMenuItem>
    <DropdownMenuItem
        v-if="canDelete('databases')"
        @click="openDeleteDialog(database)"
        class="text-destructive"
    >
        <Trash2 class="mr-2 h-4 w-4" />
        Excluir
    </DropdownMenuItem>
</DropdownMenuContent>
```

### Setup Inicial (uma vez por feature)

**No composable `usePermissions`:**
```ts
export function usePermissions() {
    const permissions = computed(() =>
        (page.props.userPermissions as string[]) || []
    );

    const canView = (resource: string): boolean => {
        return permissions.value.includes(`${resource}.view`);
    };

    const canCreate = (resource: string): boolean => {
        return permissions.value.includes(`${resource}.create`);
    };

    const canEdit = (resource: string): boolean => {
        return permissions.value.includes(`${resource}.edit`);
    };

    const canDelete = (resource: string): boolean => {
        return permissions.value.includes(`${resource}.delete`);
    };

    return { permissions, canView, canCreate, canEdit, canDelete };
}
```

**No middleware `HandleInertiaRequests`:**
```php
$userPermissions = $user ? $user->getActualPermissions()->pluck('name')->toArray() : [];
```

**Permissões por Recurso:**
| Recurso | View | Create | Edit | Delete |
|---------|-----|--------|-----|--------|
| databases | databases.view | databases.create | databases.edit | databases.delete |
| credentials | credentials.view | credentials.create | credentials.edit | credentials.delete |
| schemas | schemas.view | schemas.create | schemas.edit | schemas.delete |

### Checklist para Nova Feature

Ao adicionar uma nova feature com UI:

- [ ] **Feature Flag**: Criar feature em `config/features.php`
- [ ] **Sidebar**: Validar `activeFeatures?.includes()` E `canView()`
- [ ] **Botão Criar**: Validar `canCreate('resource')`
- [ ] **Botão Editar**: Validar `canEdit('resource')`
- [ ] **Botão Excluir**: Validar `canDelete('resource')`
- [ ] **Controller**: Adicionar `Feature::active()` ou middleware `feature:`
- [ ] **Middleware**: Adicionar `denied.check` se aplicável

---

## Checklist para Tarefas

Ao adicionar uma nova feature:

- [ ] Use `declare(strict_types=1)`
- [ ] Use Route Model Binding (sempre!)
- [ ] Crie FormRequest para validação
- [ ] Crie Resource para respostas JSON
- [ ] Crie scopes reutilizáveis no Model (`scopeOf{Entity}()`)
- [ ] Coloque lógica de negócio em Service
- [ ] Use PHP Attributes ao invés de métodos mágicos
- [ ] Use Queue com Laravel 13 queue routing
- [ ] Crie Policy para autorização
- [ ] Escreva testes (Services, Policies, Scopes)
- [ ] Use KSUID para entidades principais

---

## Exemplo Completo: Nova Feature

### Adicionando "Schema" management

```php
// 1. Model com strict types, property hooks, KSUID
<?php
declare(strict_types=1);

namespace App\Models;

use App\Traits\HasKsuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schema extends Model
{
    use HasKsuid;

    protected $fillable = ['name', 'table_name', 'database_id'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function database(): BelongsTo
    {
        return $this->belongsTo(Database::class);
    }

    public function scopeOfDatabase($query, string $databaseId)
    {
        return $query->where('database_id', $databaseId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

// 2. FormRequest com validação + autorização
<?php
declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateSchemaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Schema::class);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'table_name' => ['required', 'string', 'max:100'],
            'database_id' => ['required', 'string', 'exists:databases,id'],
        ];
    }
}

// 3. Service com regras de negócio
<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\Database;
use App\Models\Schema;

class SchemaService
{
    public function createForDatabase(Database $database, array $data): Schema
    {
        // Regra de negócio: validar nome único no database
        if ($database->schemas()->where('name', $data['name'])->exists()) {
            throw new \InvalidArgumentException('Schema name must be unique within database');
        }

        return $database->schemas()->create($data);
    }
}

// 4. Controller com route model binding + orquestração
<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateSchemaRequest;
use App\Http\Resources\SchemaResource;
use App\Models\Database;
use App\Models\Schema;
use App\Services\SchemaService;
use Illuminate\Http\RedirectResponse;

class SchemaController extends Controller
{
    public function __construct(
        private SchemaService $service
    ) {}

    public function store(CreateSchemaRequest $request, Database $database): SchemaResource
    {
        $schema = $this->service->createForDatabase(
            $database,
            $request->validated()
        );

        return new SchemaResource($schema);
    }

    public function show(Database $database, Schema $schema): SchemaResource
    {
        // Route model binding resolve ambos automaticamente
        return new SchemaResource($schema);
    }
}

// 5. Route com binding automático
Route::post('/databases/{database}/schemas', [SchemaController::class, 'store']);
Route::get('/databases/{database}/schemas/{schema}', [SchemaController::class, 'show']);

// 6. Policy para autorização
<?php
declare(strict_types=1);

namespace App\Policies;

use App\Models\Schema;
use App\Models\User;

class SchemaPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-schema');
    }

    public function view(User $user, Schema $schema): bool
    {
        return $user->canAccessDatabase($schema->database_id);
    }
}
```

---

## Laravel Boost - Performance

```bash
# Comandos para otimização
php artisan boost:optimize      # Cache tudo
php artisan boost:clear         # Limpa caches
php artisan boost:status        # Status da otimização
```

No código:

```php
// Usar boost para queries pesadas
use Laravel\Boost\Attributes\Boosted;

#[Boosted]
public function getLargeDataset(): Collection
{
    return Database::with(['credentials', 'schemas'])->get();
}
```

---

## Componentes shadcn-vue

### Componentes Faltantes

Quando precisar usar um componente do shadcn-vue que não existe no projeto, **AVISE O USUÁRIO** para instalar.

**Como identificar:**
- Ao importar de `@/components/ui/...` e o arquivo não existir
- Erro "Failed to resolve import" no Vite

**O que fazer:**
1. Pare e avise: "O componente `X` do shadcn não está instalado. Quer que instale?"
2. Aguarde confirmação antes de tentar criar manualmente

**Componentes instalados:**
- button, card, input, label, badge, table, tabs, dialog, dropdown-menu, separator, avatar, tooltip, sheet, skeleton, alert, select, form

**Não tente recriar componentes shadcn manualmente** - deixe o usuário instalar via CLI oficial.

---

## Conclusão

**Lembre-se:** Se o Laravel tem uma feature, use-a. O código deve ser expressivo, não esperto.

Quando em dúvida:
1. Cheque a documentação oficial do Laravel 13
2. Veja exemplos existentes no código (DatabaseController é um bom reference)
3. Siga as convenções estabelecidas

```php
// Código limpo é melhor que código "esperto"
Database::active()->ofStatus('ready')->get(); // ✅
Database::where('is_active', 1)->where('status', 'ready')->get(); // ❌
```
