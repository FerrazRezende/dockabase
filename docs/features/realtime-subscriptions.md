# Realtime Subscriptions

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft |
| Priority | P1 (High) |
| Phase | 5 |
| Feature Flag | `realtime` |
| Dependencies | Dynamic API - CRUD, RBAC & RLS |

---

## User Story

**As a** desenvolvedor frontend
**I want to** me inscrever para receber atualizações em tempo real de tabelas específicas
**So that** minha aplicação reage instantaneamente a mudanças de dados sem polling

---

## Acceptance Criteria

```gherkin
Scenario: Inscrever-se em tabela
  Given estou autenticado com JWT
  And tenho permissão "products.select"
  When me conecto ao WebSocket e envio:
    | event | subscribe |
    | channel | products |
  Then recebo confirmação de inscrição
  And status 200
```

```gherkin
Scenario: Receber evento de INSERT
  Given estou inscrito no canal "products"
  When outro usuário cria um produto via API
  Then recebo evento via WebSocket:
    | event | INSERT |
    | table | products |
    | data | { "id": "...", "name": "New Product", ... } |
    | timestamp | 1709827200 |
```

```gherkin
Scenario: Receber evento de UPDATE
  Given estou inscrito no canal "products"
  When um produto é atualizado
  Then recebo evento:
    | event | UPDATE |
    | table | products |
    | data | { "id": "...", "name": "Updated", ... } |
    | old | { "name": "Old Name" } |
```

```gherkin
Scenario: Receber evento de DELETE
  Given estou inscrito no canal "products"
  When um produto é deletado
  Then recebo evento:
    | event | DELETE |
    | table | products |
    | data | { "id": "..." } |
```

```gherkin
Scenario: Filtrar eventos por condição
  When me inscrevo com filtro:
    | channel | products |
    | filter | price > 100 |
  Then recebo eventos apenas de produtos com price > 100
```

```gherkin
Scenario: Cancelar inscrição
  Given estou inscrito no canal "products"
  When envio:
    | event | unsubscribe |
    | channel | products |
  Then não recebo mais eventos do canal
```

```gherkin
Scenario: RLS aplicado em realtime
  Given sou usuário do projeto "project-a"
  And estou inscrito no canal "products"
  When produto é criado no projeto "project-b"
  Then NÃO recebo o evento (RLS filtra)
```

```gherkin
Scenario: Sem permissão para tabela
  Given NÃO tenho permissão "orders.select"
  When tento me inscrever no canal "orders"
  Then recebo erro:
    | event | error |
    | message | Unauthorized to subscribe to orders |
```

```gherkin
Scenario: Reconexão automática
  Given minha conexão WebSocket cai
  When reconecto
  Then sou reinscrito automaticamente nos canais anteriores
```

```gherkin
Scenario: Múltiplas inscrições
  When me inscrevo em "products" e "orders"
  Then recebo eventos de ambos os canais
  And cada evento indica o canal de origem
```

---

## Technical Notes

### Arquitetura Realtime
```
┌─────────────┐                    ┌─────────────────┐
│   Client    │ ◄─── WebSocket ───►│  Laravel Echo   │
│  (Frontend) │                    │     Server      │
└─────────────┘                    └────────┬────────┘
                                            │
                                            ▼
                                   ┌─────────────────┐
                                   │     Redis       │
                                   │   Pub/Sub       │
                                   └────────┬────────┘
                                            │
                                            ▼
                                   ┌─────────────────┐
                                   │   PostgreSQL    │
                                   │  LISTEN/NOTIFY  │
                                   └────────┬────────┘
                                            │
                                            ▼
                                   ┌─────────────────┐
                                   │    Triggers     │
                                   │  (INSERT/UPDATE │
                                   │   /DELETE)      │
                                   └─────────────────┘
```

### PostgreSQL Trigger para NOTIFY
```sql
-- Trigger function para notificar mudanças
CREATE OR REPLACE FUNCTION notify_table_changes()
RETURNS TRIGGER AS $$
DECLARE
    payload JSON;
BEGIN
    IF TG_OP = 'INSERT' THEN
        payload = json_build_object(
            'event', 'INSERT',
            'table', TG_TABLE_NAME,
            'data', to_json(NEW),
            'timestamp', extract(epoch from now())
        );
    ELSIF TG_OP = 'UPDATE' THEN
        payload = json_build_object(
            'event', 'UPDATE',
            'table', TG_TABLE_NAME,
            'data', to_json(NEW),
            'old', to_json(OLD),
            'timestamp', extract(epoch from now())
        );
    ELSIF TG_OP = 'DELETE' THEN
        payload = json_build_object(
            'event', 'DELETE',
            'table', TG_TABLE_NAME,
            'data', json_build_object('id', OLD.id),
            'timestamp', extract(epoch from now())
        );
    END IF;

    PERFORM pg_notify(
        'realtime_' || TG_TABLE_NAME,
        payload::text
    );

    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Aplicar trigger em tabela
CREATE TRIGGER products_notify
AFTER INSERT OR UPDATE OR DELETE ON products
FOR EACH ROW EXECUTE FUNCTION notify_table_changes();
```

### Laravel Event Listener
```php
class RealtimeListener implements ShouldHandleEventsAfterCommit
{
    public function handle(string $channel, string $message): void
    {
        $payload = json_decode($message, true);

        // Apply RLS filter
        $payload['project_id'] = $this->extractProjectId($payload);

        // Broadcast to Redis
        Broadcast::on("realtime.{$channel}")
            ->with($payload)
            ->toOthers();
    }
}
```

### WebSocket Protocol
```javascript
// Client -> Server (Subscribe)
{
  "event": "subscribe",
  "channel": "products",
  "filter": "price > 100",  // optional
  "auth": {
    "token": "Bearer <jwt>"
  }
}

// Server -> Client (Confirmation)
{
  "event": "subscribed",
  "channel": "products",
  "subscription_id": "sub_abc123"
}

// Server -> Client (Data Event)
{
  "event": "INSERT",
  "channel": "products",
  "data": { "id": "...", "name": "New Product" },
  "timestamp": 1709827200
}

// Client -> Server (Unsubscribe)
{
  "event": "unsubscribe",
  "channel": "products"
}
```

### Endpoints & Channels
| Type | Endpoint/Channel | Description |
|------|------------------|-------------|
| WebSocket | `/realtime/v1/ws` | Conexão WebSocket |
| Channel | `realtime.{table}` | Eventos da tabela |
| Channel | `realtime.{table}.{filter_hash}` | Eventos filtrados |

### Files to Create
```
app/
├── Domain/Realtime/
│   ├── Controllers/
│   │   └── RealtimeController.php
│   ├── Services/
│   │   ├── SubscriptionService.php
│   │   ├── FilterEvaluatorService.php
│   │   └── RlsFilterService.php
│   ├── Events/
│   │   └── TableChangedEvent.php
│   ├── Listeners/
│   │   └── PostgresNotifyListener.php
│   └── Middleware/
│       └── AuthenticateWebSocket.php
├── Services/
│   └── TriggerManagerService.php

database/
└── migrations/
    └── create_realtime_triggers_function.php

resources/js/
└── composables/
    └── useRealtime.ts
```

### Frontend Composable (Vue 3)
```typescript
// resources/js/composables/useRealtime.ts
export function useRealtime(table: string, filter?: string) {
  const events = ref<RealtimeEvent[]>([])
  const isConnected = ref(false)

  const ws = new WebSocket(`${WS_URL}/realtime/v1/ws`)

  ws.onopen = () => {
    ws.send(JSON.stringify({
      event: 'subscribe',
      channel: table,
      filter,
      auth: { token: `Bearer ${getAccessToken()}` }
    }))
    isConnected.value = true
  }

  ws.onmessage = (event) => {
    const data = JSON.parse(event.data)
    events.value.push(data)
  }

  onUnmounted(() => {
    ws.send(JSON.stringify({ event: 'unsubscribe', channel: table }))
    ws.close()
  })

  return { events, isConnected }
}
```

---

## Security Considerations

- [ ] Autenticação JWT obrigatória no WebSocket
- [ ] Verificar permissão antes de permitir inscrição
- [ ] Aplicar RLS no broadcasting de eventos
- [ ] Rate limiting de inscrições por conexão
- [ ] Limite de canais por usuário
- [ ] Validar filtros contra SQL injection
- [ ] Timeout de conexões inativas
