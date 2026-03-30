# Async Database Creation + Notifications - Design Spec

> **Status:** Draft
> **Created:** 2026-03-29
> **Author:** Claude + User

## Overview

Sistema de criação assíncrona de databases com feedback em tempo real via WebSocket, notificações via toast e notification center, e histórico completo de alterações de schema.

## Goals

1. Criar databases de forma assíncrona via RabbitMQ
2. Mostrar progresso em tempo real na página de show
3. Notificar usuário via toast + notification center
4. Manter histórico de todas as alterações de schema

## User Flow

```
┌─────────────────────────────────────────────────────────────┐
│  1. Usuário preenche formulário                             │
│  2. POST /app/databases → cria registro com status=pending  │
│  3. Dispara job para RabbitMQ                               │
│  4. Redireciona para /app/databases/{id} (Show page)        │
│  5. Página mostra timeline com progress em tempo real       │
│  6. WebSocket atualiza steps conforme job avança            │
│  7. Ao final: toast "Database criado!" + notificação        │
└─────────────────────────────────────────────────────────────┘
```

## Architecture

```
┌──────────────┐     ┌──────────────┐     ┌──────────────┐
│   Controller │────▶│    Queue     │────▶│     Job      │
│  (dispatch)  │     │  (RabbitMQ)  │     │ (Worker)     │
└──────────────┘     └──────────────┘     └──────┬───────┘
                                                  │
                    ┌─────────────────────────────┘
                    ▼
┌──────────────────────────────────────────────────────────────┐
│                     JOB FLOW                                  │
│                                                              │
│  foreach ($steps as $step) {                                 │
│      1. update($database, ['current_step' => $step]);        │
│      2. broadcast(DatabaseStepUpdated($database, $step));    │
│      3. executeStep($step);                                  │
│      4. update($database, ['progress' => ++$progress]);      │
│  }                                                           │
│                                                              │
│  final: broadcast(DatabaseCreated($database));               │
│         Notification::create([...]);                         │
└──────────────────────────────────────────────────────────────┘
                    │
                    ▼
┌──────────────┐     ┌──────────────┐
│   Reverb     │────▶│   Laravel    │
│  (WebSocket) │     │    Echo      │
└──────────────┘     └──────────────┘
                    │
                    ▼
             ┌──────────────┐
             │   Frontend   │
             │  (realtime)  │
             └──────────────┘
```

## Components

### Backend

| Componente | Arquivo | Descrição |
|------------|---------|-----------|
| Job | `app/Jobs/CreateDatabaseJob.php` | Executa criação do database |
| Event (step) | `app/Events/DatabaseStepUpdated.php` | Broadcast step update |
| Event (complete) | `app/Events/DatabaseCreated.php` | Broadcast completion |
| Notification | `app/Notifications/DatabaseCreatedNotification.php` | Notification para o usuário |
| Service | `app/Services/DatabaseProvisioningService.php` | Lógica de provisioning |
| Migration | `create_database_schema_histories_table.php` | Histórico de schema |

### Frontend

| Componente | Arquivo | Descrição |
|------------|---------|-----------|
| Toast | `sonner` (shadcn-vue) | Notificações temporárias |
| Timeline | `resources/js/components/CreationTimeline.vue` | Steps horizontais |
| Notification Center | `resources/js/components/NotificationCenter.vue` | Lista de notificações |
| Types | `resources/js/types/notification.ts` | TypeScript types |

### Infrastructure

| Componente | Config | Descrição |
|------------|--------|-----------|
| RabbitMQ | `config/queue.php` | Driver de queue |
| Reverb | `config/reverb.php` | WebSocket server |
| Echo | `resources/js/bootstrap.js` | WebSocket client |

## Database Creation Steps (Horizontal Timeline)

```
┌──────────────────────────────────────────────────────────────────────────┐
│                                                                          │
│  ●━━━━━━━●━━━━━━━━●━━━━━━━━●━━━━━━━━●━━━━━━━━●━━━━━━━━●               │
│  │       │        │        │        │        │        │               │
│  ▼       ▼        ▼        ▼        ▼        ▼        ▼               │
│                                                                          │
│  ✓       ⏳       ○       ○       ○       ○       ○              │
│  Valid   Criando  Config   Migra    Perms   Teste   Pronto          │
│                                                                          │
└──────────────────────────────────────────────────────────────────────────┘
```

### Step Details

| # | Step | Descrição |
|---|------|-----------|
| 1 | Validando | Valida schema e parâmetros |
| 2 | Criando | Cria banco PostgreSQL |
| 3 | Config | Configura extensions (uuid-ossp, pgcrypto) |
| 4 | Migra | Aplica migrations base |
| 5 | Perms | Configura permissões |
| 6 | Teste | Testa conexão |
| 7 | Pronto | Database disponível |

### Step States

| Estado | Ícone | Cor |
|--------|-------|-----|
| pending | ○ | cinza |
| running | ⏳ | azul animado |
| completed | ✓ | verde |
| failed | ✗ | vermelho |

## Notification System

### Toast (Sonner)

- **Posição:** bottom-right
- **Duração:** 3-5 segundos
- **Tipos:** success, error, warning, info
- **Exemplos:**
  - "Database criado com sucesso!"
  - "Falha ao criar database"
  - "Schema atualizado"

### Notification Center (Sino)

- **Badge:** Contador de não lidas
- **Retenção:** 7 dias
- **Ações:**
  - Marcar como lida
  - Marcar todas como lidas
  - Ver detalhes

### Notification Types

| Tipo | Descrição |
|------|-----------|
| `database_created` | Database criado com sucesso |
| `database_failed` | Falha na criação |
| `schema_changed` | Alteração no schema |
| `backup_completed` | Backup finalizado |

## Schema History

### Migration

```sql
CREATE TABLE database_schema_histories (
    id BIGINT PRIMARY KEY,
    database_id CHAR(27) NOT NULL,
    action VARCHAR(50) NOT NULL,
    table_name VARCHAR(255),
    column_name VARCHAR(255),
    old_value JSONB,
    new_value JSONB,
    user_id BIGINT,
    created_at TIMESTAMP,

    FOREIGN KEY (database_id) REFERENCES databases(id)
);
```

### Actions Tracked

| Ação | Descrição |
|------|-----------|
| `table_created` | Criou tabela |
| `table_dropped` | Removeu tabela |
| `column_added` | Adicionou coluna |
| `column_dropped` | Removeu coluna |
| `column_altered` | Alterou tipo/propriedades |
| `index_created` | Criou índice |
| `index_dropped` | Removeu índice |
| `constraint_added` | Adicionou constraint |
| `constraint_dropped` | Removeu constraint |

### UI

- **Localização:** Aba "History" na página do database
- **Formato:** Timeline reverso (mais recente primeiro)
- **Filtros:** Por tabela, ação, usuário, período

## API Endpoints

### Notifications

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/notifications` | Lista notificações |
| POST | `/api/notifications/{id}/read` | Marca como lida |
| POST | `/api/notifications/read-all` | Marca todas como lidas |
| GET | `/api/notifications/unread-count` | Contador de não lidas |

### Database Status (WebSocket)

| Canal | Event | Payload |
|-------|-------|---------|
| `database.{id}` | `DatabaseStepUpdated` | `{ step, progress, status }` |
| `database.{id}` | `DatabaseCreated` | `{ database }` |
| `database.{id}` | `DatabaseFailed` | `{ error }` |

## Dependencies

### Composer

```json
{
    "vyuldashev/laravel-queue-rabbitmq": "^14.0",
    "laravel/reverb": "^1.0"
}
```

### NPM

```json
{
    "laravel-echo": "^1.15",
    "pusher-js": "^8.0",
    "sonner": "^1.0"
}
```

## Implementation Order

1. Install dependencies (RabbitMQ, Reverb, Sonner)
2. Create migration for `database_schema_histories` and `notifications`
3. Create Notification model and API endpoints
4. Create Job and Events
5. Create DatabaseProvisioningService
6. Update DatabaseController to dispatch job
7. Create Vue components (Timeline, NotificationCenter)
8. Configure WebSocket (Echo, Reverb)
9. Update Show page with timeline
10. Add toast notifications

## Success Criteria

- [ ] Database creation is async via RabbitMQ
- [ ] Timeline shows 7 steps horizontally with real-time updates
- [ ] Toast appears when database is created
- [ ] Notification center shows history (7 days)
- [ ] Schema changes are logged to `database_schema_histories`
- [ ] WebSocket works via Laravel Echo + Reverb
