# Dynamic API - Validação Dinâmica

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft |
| Priority | P1 (High) |
| Phase | 4 |
| Feature Flag | `dynamic-api` |
| Dependencies | Dynamic API - CRUD, Schema Builder |

---

## User Story

**As a** desenvolvedor frontend
**I want to** que a API valide automaticamente os dados enviados baseado no schema da tabela
**So that** não preciso definir regras de validação manualmente e os dados são consistentes

---

## Acceptance Criteria

```gherkin
Scenario: Campo obrigatório não informado
  Given tabela "products" tem coluna "name" com NOT NULL
  When POST para `/api/v1/products` com:
    | price | 100 |
  Then recebo status 422
  And body contém:
    | error | validation_error |
    | details.name | ["The name field is required."] |
```

```gherkin
Scenario: Tipo de dado incorreto
  Given coluna "price" é do tipo decimal
  When POST para `/api/v1/products` com:
    | name | Product |
    | price | not_a_number |
  Then recebo status 422
  And body contém:
    | details.price | ["The price must be a number."] |
```

```gherkin
Scenario: String excede tamanho máximo
  Given coluna "name" é varchar(100)
  When POST para `/api/v1/products` com:
    | name | <string com 150 caracteres> |
  Then recebo status 422
  And body contém:
    | details.name | ["The name may not be greater than 100 characters."] |
```

```gherkin
Scenario: Valor fora do range numérico
  Given coluna "quantity" é integer com check constraint >= 0
  When POST para `/api/v1/products` com:
    | quantity | -5 |
  Then recebo status 422
```

```gherkin
Scenario: UUID inválido
  Given coluna "category_id" é do tipo uuid
  When POST para `/api/v1/products` com:
    | category_id | not-a-uuid |
  Then recebo status 422
  And body contém:
    | details.category_id | ["The category id must be a valid UUID."] |
```

```gherkin
Scenario: JSONB inválido
  Given coluna "metadata" é do tipo jsonb
  When POST para `/api/v1/products` com:
    | metadata | {invalid json} |
  Then recebo status 422
  And body contém:
    | details.metadata | ["The metadata must be a valid JSON."] |
```

```gherkin
Scenario: Array com tipo incorreto
  Given coluna "tags" é do tipo text[]
  When POST para `/api/v1/products` com:
    | tags | "not an array" |
  Then recebo status 422
```

```gherkin
Scenario: Validação de foreign key
  Given coluna "category_id" referencia "categories.id"
  When POST para `/api/v1/products` com:
    | category_id | <uuid que não existe> |
  Then recebo status 422
  And body contém:
    | details.category_id | ["The selected category id is invalid."] |
```

```gherkin
Scenario: Dados válidos são aceitos
  Given todos os campos estão corretos
  When POST para `/api/v1/products` com dados válidos
  Then recebo status 201
  And registro é criado
```

```gherkin
Scenario: PATCH valida apenas campos enviados
  Given tabela "products" tem coluna obrigatória "name"
  When PATCH para `/api/v1/products/123` com:
    | price | 200 |
  Then apenas "price" é validado
  And "name" não é requerido (não está sendo alterado)
```

```gherkin
Scenario: Unique constraint violation
  Given coluna "email" tem constraint UNIQUE
  And existe usuário com email "test@example.com"
  When POST para `/api/v1/users` com:
    | email | test@example.com |
  Then recebo status 422
  And body contém:
    | details.email | ["The email has already been taken."] |
```

---

## Technical Notes

### Mapeamento de Tipos PostgreSQL → Laravel Validation
| PostgreSQL | Laravel Rule |
|------------|--------------|
| `varchar(n)` | `string`, `max:n` |
| `text` | `string` |
| `integer` | `integer` |
| `bigint` | `integer` |
| `decimal(p,s)` | `numeric`, `regex:/^\d{1,p-s}(\.\d{1,s})?$/` |
| `boolean` | `boolean` |
| `uuid` | `uuid` |
| `timestamp` | `date` |
| `date` | `date` |
| `jsonb` / `json` | `json` |
| `text[]` | `array` |
| `inet` | `ip` |

### Dynamic FormRequest
```php
class DynamicFormRequest extends FormRequest
{
    public function rules(): array
    {
        $table = $this->route('table');
        $schema = app(SchemaCacheService::class)->getSchema($table);

        return $this->buildRulesFromSchema($schema);
    }

    private function buildRulesFromSchema(array $schema): array
    {
        $rules = [];

        foreach ($schema['columns'] as $column) {
            $columnRules = [];

            // Required?
            if ($column['is_nullable'] === 'NO' && !$column['column_default']) {
                $columnRules[] = 'required';
            }

            // Type mapping
            $columnRules = array_merge(
                $columnRules,
                $this->getTypeRules($column['data_type'], $column)
            );

            // Foreign key?
            if ($fk = $this->getForeignKey($column['column_name'])) {
                $columnRules[] = "exists:{$fk['foreign_table']},{$fk['foreign_column']}";
            }

            $rules[$column['column_name']] = $columnRules;
        }

        return $rules;
    }
}
```

### Schema Cache Service
```php
class SchemaCacheService
{
    public function getSchema(string $table): array
    {
        return Cache::remember("schema:{$table}", 300, function () use ($table) {
            return [
                'columns' => $this->getColumns($table),
                'constraints' => $this->getConstraints($table),
                'foreign_keys' => $this->getForeignKeys($table),
            ];
        });
    }

    private function getColumns(string $table): array
    {
        return DB::select("
            SELECT
                column_name,
                data_type,
                character_maximum_length,
                is_nullable,
                column_default
            FROM information_schema.columns
            WHERE table_name = ?
            ORDER BY ordinal_position
        ", [$table]);
    }
}
```

### Files to Create
```
app/
├── Domain/Api/
│   ├── Requests/
│   │   └── DynamicFormRequest.php
│   ├── Services/
│   │   ├── SchemaCacheService.php
│   │   ├── DynamicValidationService.php
│   │   └── ForeignKeyResolverService.php
│   └── Enums/
│       └── PostgresTypeEnum.php
```

---

## Security Considerations

- [ ] Cache do schema invalidado após migrations
- [ ] Não expor estrutura interna em mensagens de erro
- [ ] Validar contra colunas existentes (evitar injection)
- [ ] Rate limiting em requests com erro de validação
- [ ] Log de tentativas com dados maliciosos
