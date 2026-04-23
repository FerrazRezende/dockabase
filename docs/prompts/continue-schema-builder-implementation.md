# Prompt: Continuar Implementação Schema Builder

Copia e cola este prompt para continuar a implementação do Schema Builder (visualização apenas):

---

## Contexto

Estou implementando o **Schema Builder** para o DockaBase. O escopo atual é **apenas visualização** de schemas (estilo pgAdmin), sem criação de tabelas.

**Já implementado:**
- ✅ Frontend completo (SchemaBrowser, SchemaFolder, TableTreeItem, ColumnBadge, DataView)
- ✅ Composable `useSchemaBrowser.ts`
- ✅ Types TypeScript `types/schema.ts`
- ✅ Aba "Schema" na página de Database Show (com PvTabs)
- ✅ Traduções PT, EN, ES validadas
- ✅ Feature flag `schema-builder` configurada

**Precisa implementar:**
- Backend: `SchemaIntrospectionService`, `SchemaController`, Policy, Resources
- Rotas
- Testes

**Arquivos de referência:**
- Spec: `docs/superpowers/specs/2026-04-16-schema-builder-design.md`
- Plan: `docs/superpowers/plans/2026-04-16-schema-builder.md`

---

## Instruções

Por favor, continue a implementação do Schema Builder seguindo estas regras:

1. **COMECE PELO TRACK 1 do plano** (`SchemaIntrospectionService`)
   - TDD primeiro: escreve os testes antes de implementar
   - Usa `declare(strict_types=1);` em todos os arquivos PHP
   - Segue as convenções do projeto (CLAUDE.md)

2. **Conexão dinâmica ao PostgreSQL**
   - O Database model tem: `host`, `port`, `database_name`, `username`, `password`
   - Precisa criar conexão temporária para ler `information_schema`
   - Pode usar `DB::connection()->setPdoConnection()` ou criar config dinâmica

3. **Queries do information_schema**
   - `getSchemas()`: SELECT de `information_schema.schemata` (filtrar system catalogs)
   - `getTables()`: SELECT de `information_schema.tables` com COUNT(*)
   - `getColumns()`: SELECT de `information_schema.columns` + `key_column_usage`
   - `getTableData()`: SELECT direto da tabela com OFFSET/LIMIT

4. **Políticas e Permissões**
   - User só pode ver schema se tiver credential attached ao database
   - Verifica em `DatabasePolicy@viewSchema()`

5. **Após cada implementação**
   - Roda os testes: `php artisan test --filter=SchemaIntrospection`
   - Verifica se está funcionando
   - Commite com mensagem descritiva

6. **IGNORA o que não está no escopo**
   - ❌ Não cria dialogs de "New Table"
   - ❌ Não implementa creation wizard
   - ❌ Não implementa migrations
   - Apenas visualização!

7. **Quando terminar o backend**
   - Testa no navegador: abre `/app/databases/{id}` e clica na aba Schema
   - Verifica se aparece a lista de schemas → tabelas
   - Verifica se clicando numa tabela mostra os dados
   - Reporta qualquer erro

---

## Track Priorities

Implementa nesta ordem:

**Track 1:** SchemaIntrospectionService (com TDD)
**Track 2:** FormRequest (TableDataRequest)
**Track 3:** Resources (SchemaResource, TableDataResource)
**Track 4:** DatabasePolicy (add viewSchema method)
**Track 5:** SchemaController (com testes)
**Track 6:** Routes (registrar em web.php)

**Tracks 7-11:** Já estão feitos (frontend)

---

## Informações Úteis

**Estrutura do Database:**
```php
Database {
  id: string (KSUID)
  name: string
  database_name: string (ex: "dockabase_prod")
  host: string (ex: "localhost")
  port: int (ex: 5432)
  username: string (ex: "dockabase")
  password: string
  credentials: HasMany through pivot
}
```

**Credenciais do usuário:**
```php
// Verifica se user tem acesso
$database->credentials()
    ->whereHas('users', fn($q) => $q->where('users.id', $user->id))
    ->exists();
```

**Teste de conexão:**
```php
// Pode testar com pg_connect ou PDO
$dsn = "pgsql:host={$host};port={$port};dbname={$database}";
$pdo = new PDO($dsn, $username, $password);
```

---

## Boa sorte! 🚀

Se encontrar blockers, pergunta! Não fiques preso em algo por muito tempo.
