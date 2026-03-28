# Dynamic API - CRUD Operations

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft |
| Priority | P0 (Critical) |
| Phase | 4 |
| Feature Flag | `dynamic-api` |
| Dependencies | RBAC & RLS Integration |

---

## User Story

**As a** desenvolvedor frontend
**I want to** acessar qualquer tabela do projeto via API REST automática
**So that** não preciso criar endpoints manuais para cada recurso

---

## Acceptance Criteria

```gherkin
Scenario: Listar registros (GET)
  Given tabela "products" existe com 5 registros
  And tenho permissão "products.select"
  When GET para `/api/v1/products`
  Then recebo array com 5 produtos
  And status 200
  And header `Content-Range: 0-4/5`
```

```gherkin
Scenario: Criar registro (POST)
  Given tenho permissão "products.insert"
  When POST para `/api/v1/products` com body:
    | name | Notebook Dell |
    | price | 3500.00 |
  Then registro é criado com UUID
  And status 201
  And body contém:
    | id | <uuid> |
    | name | Notebook Dell |
    | price | 3500.00 |
    | created_at | <timestamp> |
```

```gherkin
Scenario: Obter registro por ID (GET)
  Given existe produto com id "01ARZ3..."
  When GET para `/api/v1/products/01ARZ3...`
  Then recebo dados do produto
  And status 200
```

```gherkin
Scenario: Atualizar registro (PATCH)
  Given tenho permissão "products.update"
  And existe produto com id "01ARZ3..."
  When PATCH para `/api/v1/products/01ARZ3...` com:
    | price | 3200.00 |
  Then produto é atualizado
  And status 200
  And body contém price atualizado
```

```gherkin
Scenario: Deletar registro (DELETE)
  Given tenho permissão "products.delete"
  And existe produto com id "01ARZ3..."
  When DELETE para `/api/v1/products/01ARZ3...`
  Then produto é removido (soft delete)
  And status 204
```

```gherkin
Scenario: Tabela não existe
  When GET para `/api/v1/nonexistent`
  Then recebo status 404
  And body contém:
    | error | table_not_found |
```

```gherkin
Scenario: Sem permissão para operação
  Given NÃO tenho permissão "products.delete"
  When DELETE para `/api/v1/products/01ARZ3...`
  Then recebo status 403
```

```gherkin
Scenario: Validação de colunas
  Given tabela "products" tem colunas [id, name, price]
  When POST para `/api/v1/products` com:
    | name | Produto |
    | invalid_column | value |
  Then recebo status 422
  And body contém:
    | error | invalid_column |
    | message | Column 'invalid_column' does not exist |
```

```gherkin
Scenario: Select de colunas específicas
  When GET para `/api/v1/products?select=id,name`
  Then recebo apenas colunas id e name
  And outras colunas não são retornadas
```

---

## Technical Notes

### Roteamento Dinâmico
```php
// routes/api.php
Route::middleware(['auth:api', 'rls.context', 'dynamic.permission'])
    ->any('/v1/{table}/{id?}', [DynamicController::class, 'handle'])
    ->where('table', '[a-z_]+')
    ->where('id', '[a-zA-Z0-9]+');
```

### Controller Structure
```php
class DynamicController
{
    public function handle(Request $request, string $table, ?string $id = null)
    {
        return match ($request->method()) {
            'GET' => $id ? $this->show($table, $id) : $this->index($table),
            'POST' => $this->store($table),
            'PATCH' => $this->update($table, $id),
            'DELETE' => $this->destroy($table, $id),
            default => abort(405),
        };
    }
}
```

### Response Format
```json
// GET /api/v1/products (List)
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

// GET /api/v1/products/{id} (Single)
{
  "id": "...",
  "name": "Product 1",
  "price": 100,
  "created_at": "2024-03-01T10:00:00Z",
  "updated_at": "2024-03-01T10:00:00Z"
}

// POST /api/v1/products (Created)
{
  "id": "...",
  "name": "New Product",
  "price": 150,
  "created_at": "2024-03-01T10:00:00Z"
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
| GET | `/api/v1/{table}` | Listar registros |
| GET | `/api/v1/{table}/{id}` | Obter registro |
| POST | `/api/v1/{table}` | Criar registro |
| PATCH | `/api/v1/{table}/{id}` | Atualizar registro |
| DELETE | `/api/v1/{table}/{id}` | Deletar registro |

### Files to Create
```
app/
├── Domain/Api/
│   ├── Controllers/
│   │   └── DynamicController.php
│   ├── Services/
│   │   ├── DynamicQueryService.php
│   │   ├── SchemaCacheService.php
│   │   └── TableValidationService.php
│   ├── Resources/
│   │   └── DynamicResource.php
│   └── Middleware/
│       └── ValidateTableExists.php
```

### Schema Cache (Redis)
```php
// Cache do schema por 5 minutos
$cacheKey = "schema:{$projectId}:{$table}";
$columns = Cache::remember($cacheKey, 300, function () use ($table) {
    return DB::select("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns
        WHERE table_name = ?
        ORDER BY ordinal_position
    ", [$table]);
});
```

---

## Security Considerations

- [ ] Validar nome da tabela contra injection
- [ ] Verificar permissão antes de cada operação
- [ ] Whitelist de colunas válidas
- [ ] RLS aplicado automaticamente via PostgreSQL
- [ ] Rate limiting por projeto
- [ ] Soft delete por padrão
- [ ] Log de operações sensíveis
