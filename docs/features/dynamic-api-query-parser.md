# Dynamic API - Query Parser

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft |
| Priority | P0 (Critical) |
| Phase | 4 |
| Feature Flag | `dynamic-api` |
| Dependencies | Dynamic API - CRUD |

---

## User Story

**As a** desenvolvedor frontend
**I want to** filtrar, ordenar e paginar dados usando query params no estilo PostgREST
**So that** posso buscar exatamente os dados que preciso sem endpoints customizados

---

## Acceptance Criteria

```gherkin
Scenario: Filtro de igualdade
  When GET para `/api/v1/products?status=eq.active`
  Then recebo produtos onde status = 'active'
```

```gherkin
Scenario: Filtro de desigualdade
  When GET para `/api/v1/products?status=neq.inactive`
  Then recebo produtos onde status != 'inactive'
```

```gherkin
Scenario: Filtro de maior/menor
  When GET para `/api/v1/products?price=gte.100&price=lt.500`
  Then recebo produtos onde price >= 100 AND price < 500
```

```gherkin
Scenario: Filtro LIKE
  When GET para `/api/v1/products?name=like.*notebook*`
  Then recebo produtos onde name LIKE '%notebook%'
```

```gherkin
Scenario: Filtro IN
  When GET para `/api/v1/products?category=in.(electronics,books)`
  Then recebo produtos onde category IN ('electronics', 'books')
```

```gherkin
Scenario: Filtro IS NULL / NOT NULL
  When GET para `/api/v1/products?deleted_at=is.null`
  Then recebo produtos onde deleted_at IS NULL
```

```gherkin
Scenario: Ordenação ascendente
  When GET para `/api/v1/products?order=created_at.asc`
  Then recebo produtos ordenados por created_at ascendente
```

```gherkin
Scenario: Ordenação descendente
  When GET para `/api/v1/products?order=price.desc`
  Then recebo produtos ordenados por price descendente
```

```gherkin
Scenario: Ordenação múltipla
  When GET para `/api/v1/products?order=category.asc,price.desc`
  Then recebo produtos ordenados por category ASC, depois price DESC
```

```gherkin
Scenario: Paginação
  When GET para `/api/v1/products?offset=10&limit=20`
  Then recebo produtos do índice 10 ao 29
  And header `Content-Range: 10-29/100`
```

```gherkin
Scenario: Select de colunas específicas
  When GET para `/api/v1/products?select=id,name,price`
  Then recebo apenas colunas id, name, price
```

```gherkin
Scenario: Contagem de registros
  When GET para `/api/v1/products?select=count`
  Then recebo:
    | count | 150 |
```

```gherkin
Scenario: Filtro em coluna JSONB
  Given tabela "products" tem coluna JSONB "metadata"
  When GET para `/api/v1/products?metadata->>brand=eq.Dell`
  Then recebo produtos onde metadata->>'brand' = 'Dell'
```

```gherkin
Scenario: Combinação de filtros (AND)
  When GET para `/api/v1/products?status=eq.active&price=gte.100`
  Then recebo produtos onde status = 'active' AND price >= 100
```

```gherkin
Scenario: Operador inválido
  When GET para `/api/v1/products?price=invalid.100`
  Then recebo status 400
  And body contém:
    | error | invalid_operator |
```

---

## Technical Notes

### Operadores Suportados
| Operador | SQL | Exemplo |
|----------|-----|---------|
| `eq` | `=` | `?status=eq.active` |
| `neq` | `!=` | `?status=neq.inactive` |
| `gt` | `>` | `?price=gt.100` |
| `gte` | `>=` | `?price=gte.100` |
| `lt` | `<` | `?price=lt.500` |
| `lte` | `<=` | `?price=lte.500` |
| `like` | `LIKE` | `?name=like.*test*` |
| `ilike` | `ILIKE` | `?name=ilike.*TEST*` |
| `in` | `IN` | `?id=in.(1,2,3)` |
| `is` | `IS` | `?deleted_at=is.null` |
| `cs` | `@>` (contains) | `?tags=cs.{red,blue}` |
| `cd` | `<@` (contained) | `?tags=cd.{red}` |

### Query Parser Architecture
```
Request: /api/v1/products?status=eq.active&price=gte.100&order=name.asc&limit=10
                                    │
                                    ▼
                            ┌───────────────┐
                            │ QueryParser   │
                            │ Service       │
                            └───────┬───────┘
                                    │
            ┌───────────────────────┼───────────────────────┐
            │                       │                       │
            ▼                       ▼                       ▼
    ┌───────────────┐       ┌───────────────┐       ┌───────────────┐
    │ FilterParser  │       │ OrderParser   │       │ SelectParser  │
    │               │       │               │       │               │
    │ status=eq.*   │       │ order=name.*  │       │ select=id,name│
    └───────┬───────┘       └───────┬───────┘       └───────┬───────┘
            │                       │                       │
            └───────────────────────┴───────────────────────┘
                                    │
                                    ▼
                            ┌───────────────┐
                            │ QueryBuilder  │
                            │ (Eloquent)    │
                            └───────┬───────┘
                                    │
                                    ▼
                            ┌───────────────┐
                            │ PostgreSQL    │
                            └───────────────┘
```

### Filter Strategies
```php
interface FilterStrategy
{
    public function apply(Builder $query, string $column, mixed $value): Builder;
}

class EqualsFilter implements FilterStrategy
{
    public function apply(Builder $query, string $column, mixed $value): Builder
    {
        return $query->where($column, '=', $value);
    }
}

class LikeFilter implements FilterStrategy
{
    public function apply(Builder $query, string $column, mixed $value): Builder
    {
        // * becomes %
        $pattern = str_replace('*', '%', $value);
        return $query->where($column, 'LIKE', $pattern);
    }
}
```

### Enum de Operadores
```php
enum FilterOperator: string
{
    case EQ = 'eq';
    case NEQ = 'neq';
    case GT = 'gt';
    case GTE = 'gte';
    case LT = 'lt';
    case LTE = 'lte';
    case LIKE = 'like';
    case ILIKE = 'ilike';
    case IN = 'in';
    case IS = 'is';
    case CONTAINS = 'cs';
    case CONTAINED = 'cd';

    public function apply(Builder $query, string $column, mixed $value): Builder
    {
        return match ($this) {
            self::EQ => $query->where($column, '=', $value),
            self::NEQ => $query->where($column, '!=', $value),
            self::GT => $query->where($column, '>', $value),
            // ... etc
        };
    }
}
```

### Files to Create
```
app/
├── Domain/Api/
│   ├── Services/
│   │   ├── QueryParserService.php
│   │   ├── FilterParserService.php
│   │   ├── OrderParserService.php
│   │   └── SelectParserService.php
│   ├── Strategies/
│   │   ├── FilterStrategyInterface.php
│   │   ├── EqualsFilter.php
│   │   ├── LikeFilter.php
│   │   ├── InFilter.php
│   │   └── RangeFilter.php
│   └── Enums/
│       └── FilterOperatorEnum.php
```

---

## Security Considerations

- [ ] Validar nomes de colunas contra whitelist
- [ ] Limitar número de filtros por request
- [ ] Sanitizar valores antes de aplicar LIKE
- [ ] Limitar tamanho de arrays em operador IN
- [ ] Validar operadores contra enum
- [ ] Não expor mensagens de erro SQL
