# Dynamic API - CRUD Operations

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft |
| Priority | P0 (Critical) |
| Phase | 5 |
| Feature Flag | `dynamic-api` |
| Dependencies | Schema Builder, Credentials (Acesso Externo) |

---

## User Story

**As a** desenvolvedor frontend
**I want to** acessar qualquer tabela do database via API REST automática com alta performance
**So that** não preciso criar endpoints manuais para cada recurso e minha aplicação escala

---

## Arquitetura de Performance: Swoole + RabbitMQ

**Toda request da Dynamic API que acessa bancos de dados passa por uma arquitetura assíncrona de alta performance.**

O Controller NÃO executa queries diretamente. Ele recebe a request, despacha para o RabbitMQ, e retorna a resposta quando o worker processa.

```
┌──────────┐     ┌──────────────────┐     ┌───────────┐     ┌────────────┐
│  Client   │────▶│  DynamicController│────▶│  RabbitMQ │────▶│  Swoole    │
│  (app)    │◀────│  (Swoole async)   │◀────│  Queue    │◀────│  Worker    │
└──────────┘     └──────────────────┘     └───────────┘     └─────┬──────┘
                        │                                         │
                        │  1. Recebe request                      │
                        │  2. Valida auth + credential             │
                        │  3. Publica job no RabbitMQ              │
                        │  4. Aguarda resposta (Swoole coroutine)  │
                        │  5. Retorna JSON                        │
                        │                                         ▼
                        │                                  ┌────────────┐
                        │                                  │ PostgreSQL │
                        │                                  └────────────┘
```

### Fluxo detalhado

1. **Request chega** no DynamicController via Swoole (Octane)
2. **Auth + Permission check** — verifica credential e permissão (síncrono, rápido)
3. **Publica job** no RabbitMQ com payload serializado (tabela, operação, filtros, dados)
4. **Swoole coroutine aguarda** a resposta (não bloqueia outras requests)
5. **Worker processa** — executa query no PostgreSQL, aplica validações, retorna resultado
6. **Controller recebe** resultado do worker e retorna JSON

### Por que essa arquitetura

| Problema | Solução |
|----------|---------|
| Queries lentas bloqueiam workers | Swoole coroutines não bloqueiam |
| Burst de requests derruba o banco | RabbitMQ faz backpressure natural |
| Necessidade de isolamento | Workers separados por database |
| Observabilidade | Todas as queries passam pela fila (audit trail) |

### Configuração

```php
// config/dynamic-api.php
return [
    'queue' => 'dynamic-api',
    'timeout' => 30,          // seconds to wait for worker response
    'max_retries' => 2,
    'coroutine_timeout' => 10, // Swoole coroutine timeout
];
```

---

## Query Links (Shared Queries)

Funcionalidade similar ao Supabase que permite criar links compartilháveis para queries específicas.

### O que são Query Links

Um Query Link é uma URL curta que encapsula uma query (filtros, selects, ordenação) e permite compartilhar o resultado com qualquer pessoa que tenha o link — sem precisar de autenticação.

### Exemplos

```
# Query link para produtos ativos ordenados por preço
https://dockabase.com/api/v1/q/abc123

# Query link com embedding (dados de tabela relacionada)
https://dockabase.com/api/v1/q/def456

# Query link público (sem auth, read-only)
https://dockabase.com/api/v1/q/xyz789
```

### Tipos de Query Link

| Tipo | Auth | Descrição |
|------|------|-----------|
| `private` | Credential necessária | Só acessível com token válido |
| `public` | Nenhuma | Acessível por qualquer um com o link (read-only) |
| `one-time` | Nenhuma | Link expira após primeiro acesso |

### Criação de Query Links

```gherkin
Scenario: Criar query link privado
  Given tenho credential com read no database
  When POST para `/api/v1/databases/{db}/query-links` com:
    | query | { "table": "products", "filters": {"status": "eq.active"}, "select": ["id","name","price"] } |
    | type  | private |
  Then recebo query link:
    | id | ql_abc123 |
    | url | https://dockabase.com/api/v1/q/ql_abc123 |
    | type | private |
```

### Endpoints de Query Links

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/v1/databases/{db}/query-links` | Criar query link |
| GET | `/api/v1/q/{linkId}` | Executar query link |
| GET | `/api/v1/databases/{db}/query-links` | Listar query links |
| DELETE | `/api/v1/databases/{db}/query-links/{id}` | Deletar query link |

### Database

```sql
CREATE TABLE query_links (
    id VARCHAR(50) PRIMARY KEY, -- ql_{ksuid}
    database_id UUID NOT NULL REFERENCES databases(id),
    credential_id UUID REFERENCES credentials(id),
    query JSONB NOT NULL,           -- table, filters, select, order
    type VARCHAR(20) NOT NULL,      -- private, public, one-time
    accessed_count INTEGER DEFAULT 0,
    max_access_count INTEGER,        -- null = unlimited
    expires_at TIMESTAMP,
    created_by UUID NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);
```

---

## Multi-Table Views

Permite criar views que combinam dados de tabelas diferentes do mesmo database.

### O que são Views

Views são queries salvas que juntam dados de múltiplas tabelas via JOINs, permitindo visualizar dados relacionados em uma única response.

### Exemplos

```json
// View: "orders_with_customers"
{
  "name": "orders_with_customers",
  "tables": [
    { "table": "orders", "schema": "public", "alias": "o" },
    { "table": "customers", "schema": "public", "alias": "c", "join": "o.customer_id = c.id" }
  ],
  "select": ["o.id", "o.total", "o.status", "c.name as customer_name", "c.email"],
  "filters": { "o.status": "eq.completed" },
  "order": "o.created_at.desc"
}
```

### Acesso via API

```
GET /api/v1/{database}/views/{viewName}
GET /api/v1/{database}/views/{viewName}?status=eq.completed&limit=10
```

Views aceitam os mesmos query params da API dinâmica (filtros, paginação, select).

### Endpoints de Views

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/databases/{db}/views` | Listar views |
| POST | `/api/v1/databases/{db}/views` | Criar view |
| GET | `/api/v1/{db}/views/{viewName}` | Executar view |
| PUT | `/api/v1/databases/{db}/views/{viewName}` | Atualizar view |
| DELETE | `/api/v1/databases/{db}/views/{viewName}` | Deletar view |

### Database

```sql
CREATE TABLE database_views (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    database_id UUID NOT NULL REFERENCES databases(id),
    name VARCHAR(63) NOT NULL,
    definition JSONB NOT NULL,     -- tables, joins, select, filters
    created_by UUID NOT NULL,
    created_at TIMESTAMP DEFAULT NOW(),
    updated_at TIMESTAMP DEFAULT NOW(),
    UNIQUE(database_id, name)
);
```

---

## Segurança

### Camada de segurança da Dynamic API

Todas as requests para a Dynamic API passam por estas verificações:

| Camada | Descrição |
|--------|-----------|
| **Rate Limiting** | Limite de requests por credential/IP (configurável por database) |
| **Auth** | Autenticação via Sanctum token (credential-based) |
| **CORS** | Configurável por database (origins permitidas) |
| **Max tentativas login** | Lockout após N falhas consecutivas (configurável) |
| **Sessão única** | Uma sessão ativa por dispositivo por credential |
| **Throttle** | Rate limiting por IP + credential (camada dupla) |

### Middleware Stack

```php
Route::middleware([
    'auth:sanctum',           // Token da credential
    'throttle:dynamic-api',   // Rate limit (IP + credential)
    'cors:dynamic-api',       // CORS configurável por database
    'credential.access',      // Verifica credential tem acesso ao database
    'table.exists',           // Verifica tabela existe
    'permission.check',       // Verifica permissão (select, insert, etc)
])->group(function () {
    Route::any('/v1/{database}/{table}/{id?}', [DynamicController::class, 'handle']);
});
```

### Rate Limit Configuration

```php
// Por database, configurável pelo admin
[
    'rate_limits' => [
        'requests_per_minute' => 60,      // por credential
        'burst_limit' => 10,               // requests simultâneos
        'max_login_attempts' => 5,         // tentativas de login
        'lockout_duration' => 300,          // segundos de lockout
    ],
    'cors' => [
        'allowed_origins' => ['https://myapp.com'],
        'allowed_methods' => ['GET', 'POST', 'PATCH', 'DELETE'],
        'max_age' => 86400,
    ],
    'session' => [
        'max_per_credential' => 1,          // uma sessão por dispositivo
        'ttl' => 3600,                       // 1 hora
    ]
];
```

---

## Acceptance Criteria

```gherkin
Scenario: Listar registros (GET)
  Given tabela "products" existe com 5 registros
  And tenho credential com permissão read no database
  When GET para `/api/v1/{database}/products`
  Then a request é publicada no RabbitMQ
  And o worker processa e retorna os dados
  And recebo array com 5 produtos
  And status 200
  And header `Content-Range: 0-4/5`
```

```gherkin
Scenario: Criar registro (POST)
  Given tenho credential com permissão write
  When POST para `/api/v1/{database}/products` com body:
    | name | Notebook Dell |
    | price | 3500.00 |
  Then registro é criado com UUID
  And status 201
```

```gherkin
Scenario: Rate limiting ativo
  Given o database tem rate limit de 60 req/min
  When faço 61 requests em 1 minuto
  Then a 61ª recebe status 429
  And header `Retry-After: 60`
```

```gherkin
Scenario: CORS bloqueia origin não autorizada
  Given o database permite CORS de "https://myapp.com"
  When request vem de "https://evil.com"
  Then recebo status 403
```

```gherkin
Scenario: Criar query link
  Given tenho credential com read
  When POST para criar query link com filtros
  Then recebo URL compartilhável
  And acessar a URL retorna os dados filtrados
```

```gherkin
Scenario: Criar view multi-tabela
  Given existem tabelas "orders" e "customers"
  When crio view com JOIN entre elas
  Then GET na view retorna dados combinados
```

```gherkin
Scenario: Tabela não existe
  When GET para `/api/v1/{database}/nonexistent`
  Then recebo status 404
```

```gherkin
Scenario: Sem permissão
  Given minha credential tem permissão read
  When DELETE para `/api/v1/{database}/products/01ARZ3...`
  Then recebo status 403
```

---

## Technical Notes

### Roteamento Dinâmico
```php
// routes/api.php
Route::middleware(['auth:sanctum', 'throttle:dynamic-api', 'cors:dynamic-api', 'credential.access'])
    ->any('/v1/{database}/{table}/{id?}', [DynamicController::class, 'handle'])
    ->where('table', '[a-z_]+')
    ->where('id', '[a-zA-Z0-9]+');
```

### Controller Structure (Swoole + RabbitMQ)
```php
class DynamicController
{
    public function handle(Request $request, string $database, string $table, ?string $id = null)
    {
        // 1. Auth + permission já validados pelo middleware
        // 2. Monta payload do job
        $payload = [
            'database' => $database,
            'table' => $table,
            'id' => $id,
            'method' => $request->method(),
            'params' => $request->query(),
            'data' => $request->all(),
            'credential_id' => $request->user()->currentCredential()->id,
        ];

        // 3. Despacha para RabbitMQ (async via Swoole coroutine)
        $response = app(DynamicApiDispatcher::class)
            ->dispatch($payload)
            ->await(timeout: 30);

        // 4. Retorna resultado
        return match ($request->method()) {
            'GET' => new DynamicResourceCollection($response),
            'POST' => (new DynamicResource($response))->response()->setStatusCode(201),
            'PATCH' => new DynamicResource($response),
            'DELETE' => response()->noContent(),
        };
    }
}
```

### DynamicApiDispatcher
```php
class DynamicApiDispatcher
{
    public function dispatch(array $payload): PendingResponse
    {
        $jobId = Str::uuid()->toString();

        // Publica no RabbitMQ
        RabbitMQ::publish('dynamic-api', json_encode([
            'job_id' => $jobId,
            'payload' => $payload,
        ]));

        // Retorna pending response que será resolvida via Swoole coroutine
        return new PendingResponse($jobId);
    }
}
```

### Response Format
```json
// GET /api/v1/{database}/products (List)
{
  "data": [
    { "id": "...", "name": "Product 1", "price": 100 },
    { "id": "...", "name": "Product 2", "price": 200 }
  ],
  "meta": {
    "total": 2,
    "page": 1,
    "per_page": 50
  }
}

// Error
{
  "error": "validation_error",
  "message": "The given data was invalid",
  "details": {
    "name": ["The name field is required."]
  }
}
```

### Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/{database}/{table}` | Listar registros |
| GET | `/api/v1/{database}/{table}/{id}` | Obter registro |
| POST | `/api/v1/{database}/{table}` | Criar registro |
| PATCH | `/api/v1/{database}/{table}/{id}` | Atualizar registro |
| DELETE | `/api/v1/{database}/{table}/{id}` | Deletar registro |
| GET | `/api/v1/q/{linkId}` | Executar query link |
| GET | `/api/v1/{database}/views/{viewName}` | Executar view multi-tabela |

### Files to Create
```
app/
├── Http/Controllers/Api/V1/
│   ├── DynamicController.php
│   ├── QueryLinkController.php
│   └── DatabaseViewController.php
├── Http/Middleware/
│   ├── CorsMiddleware.php
│   ├── CredentialAccessMiddleware.php
│   └── SessionLimitMiddleware.php
├── Services/
│   ├── DynamicApiDispatcher.php
│   ├── DynamicQueryService.php
│   ├── SchemaCacheService.php
│   ├── QueryLinkService.php
│   └── DatabaseViewService.php
├── Jobs/
│   └── ProcessDynamicApiRequest.php   -- Worker job
├── Resources/
│   ├── DynamicResource.php
│   └── DynamicResourceCollection.php
├── Models/
│   ├── QueryLink.php
│   └── DatabaseView.php
```

---

## Security Considerations

- [ ] Rate limiting por credential (configurável por database)
- [ ] CORS configurável por database
- [ ] Max tentativas de login com lockout
- [ ] Uma sessão ativa por dispositivo por credential
- [ ] Throttle duplo (IP + credential)
- [ ] Validar nome da tabela contra injection
- [ ] Whitelist de colunas válidas
- [ ] Query links públicos são read-only
- [ ] Views validam JOINs contra tabelas que a credential tem acesso
- [ ] Soft delete por padrão
- [ ] Log de operações sensíveis
- [ ] Worker timeout para queries lentas
