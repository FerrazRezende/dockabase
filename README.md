<div align="center">

# DockaBase

**Backend as a Service self-hosted, construído com Laravel**

Uma plataforma BaaS open-source inspirada no [Supabase](https://supabase.com), projetada para gerenciar múltiplos databases PostgreSQL com interface visual, autenticação, API dinâmica e tempo real — tudo rodando na sua própria infraestrutura.

[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)](https://php.net)
[![Laravel 13](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![Vue 3](https://img.shields.io/badge/Vue-3-4FC08D?logo=vue.js&logoColor=white)](https://vuejs.org)
[![PostgreSQL 16](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)](https://postgresql.org)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)](https://docker.com)

</div>

---

## Sobre o Projeto

DockaBase é um clone funcional e simplificado do Supabase. A ideia é simples: você sobe um container e tem uma plataforma completa para gerenciar databases PostgreSQL, com painel administrativo, feature flags, credenciais de acesso, sistema de notificações em tempo real e muito mais.

### Modelo Single-Tenant com Múltiplos Databases

Cada instância do DockaBase gerencia **N databases PostgreSQL** na mesma instalação (ex: `dev`, `staging`, `prod`). Não é multi-tenant como o Supabase — cada cliente roda sua própria instância.

```
┌─────────────────────────────────────────┐
│           Instância DockaBase            │
│                                         │
│  ┌──────────┐ ┌──────────┐ ┌─────────┐  │
│  │ Database │ │ Database │ │ Database│  │
│  │   dev    │ │ staging  │ │  prod   │  │
│  └──────────┘ └──────────┘ └─────────┘  │
│         │           │           │        │
│         └─────┬─────┘───────────┘        │
│               │                          │
│     ┌─────────▼──────────┐               │
│     │   Credentials      │               │
│     │  (access control)  │               │
│     └────────────────────┘               │
└─────────────────────────────────────────┘
```

---

## Stack Tecnológica

### Backend

| Tecnologia | Versão | Uso |
|------------|--------|-----|
| PHP | 8.4 | Runtime |
| Laravel | 13 | Framework |
| PostgreSQL | 16 | Database principal |
| Redis | 7 | Cache & sessões |
| RabbitMQ | 3 | Filas & jobs assíncronos |
| MinIO | latest | Storage S3-compatible |

### Frontend

| Tecnologia | Versão | Uso |
|------------|--------|-----|
| Vue | 3.4 | Framework UI |
| TypeScript | strict | Tipagem |
| Inertia.js | 2.0 | SPA sem API |
| Tailwind CSS | 4.x | Estilização |
| shadcn-vue | latest | Componentes UI |

### Pacotes Laravel

| Pacote | Uso |
|--------|-----|
| Laravel Pennant | Feature flags (class-based) |
| Laravel Reverb | WebSockets em tempo real |
| Laravel Sanctum | API tokens |
| Laravel Breeze | Auth scaffolding (Inertia) |
| Spatie Permission | RBAC (roles & permissions) |
| Ziggy | Rotas tipadas no frontend |
| KSUID | IDs ordenáveis e distribuídos |

---

## Arquitetura

### Camadas da Aplicação

```
Request → FormRequest (validação)
       → Controller (orquestração)
       → Service (regras de negócio)
       → Model (persistência)
       → Event (broadcast)
       → Resource (transformação JSON)
```

**Responsabilidades:**

- **Controller** — orquestra: busca dados, chama Service, executa transações, dispara eventos
- **Service** — regras puras: entrada → processamento → saída (sem busca/events/transações)
- **Model** — Eloquent com Property Hooks, Scopes, Relationships, SoftDeletes
- **Resource** — transforma Model em JSON com metadados

### Dois Níveis de Acesso

| Nível | Autenticação | Uso |
|-------|-------------|-----|
| **Aplicação** (`/system/*`) | Session (web) | Painel administrativo com Spatie RBAC |
| **API** (`/api/v1/{database}/*`) | Sanctum (token) | End users acessando databases |

### Roles do Sistema

| Role | Permissões |
|------|-----------|
| `super-admin` | Acesso total |
| `admin` | create-database, manage-credentials, manage-features |
| `manager` | manage-users, view-databases |
| `user` | view-databases |

---

## Funcionalidades

### Implementadas

- [x] **Feature Flag Manager** — interface visual + API para gerenciar features com rollout gradual
- [x] **Database Creator** — criação assíncrona de databases PostgreSQL com progress em tempo real
- [x] **Credentials Manager** — gestão de credenciais e controle de acesso por database
- [x] **Sistema de Notificações** — notificações persistentes + broadcast via WebSocket
- [x] **RBAC completo** — roles e permissions com Spatie
- [x] **Multi-idioma** — PT, EN e ES com `__()` em backend e frontend
- [x] **Dark Mode** — toggle de tema com persistência
- [x] **User Activity Tracking** — log de atividades dos usuários
- [x] **Impersonation** — admin pode acessar como outro usuário

### Roadmap

- [ ] **Schema Builder** — interface visual para criar tabelas e colunas
- [ ] **Table Manager** — CRUD de dados com interface tipo planilha
- [ ] **Dynamic REST API** — API auto-gerada a partir do schema (`/api/v1/{database}/{table}`)
- [ ] **Realtime** — WebSockets com PostgreSQL LISTEN/NOTIFY
- [ ] **Storage** — MinIO com buckets e políticas de acesso
- [ ] **OTP Auth** — login sem senha via código de 6 dígitos
- [ ] **Database Encryption** — criptografia com pgcrypto
- [ ] **Automated Backups** — backups automáticos com retenção
- [ ] **Row Level Security** — políticas RLS do PostgreSQL
- [ ] **Advanced RBAC** — permissões granulares por tabela
- [ ] **MCP Server** — integração com AI assistants (Claude, GPT)

---

## Infraestrutura Docker

O ambiente de desenvolvimento sobe **7 containers** orquestrados:

```
┌──────────────────────────────────────────────────────────────┐
│                      Docker Network                          │
│                                                              │
│  ┌──────────┐   ┌──────────┐   ┌───────────┐                │
│  │  Nginx   │──▶│   App    │──▶│ Postgres  │                │
│  │  :80     │   │  PHP-FPM │   │   :5432   │                │
│  └──────────┘   └────┬─────┘   └───────────┘                │
│                      │          ┌───────────┐                │
│                      ├─────────▶│   Redis   │                │
│                      │          │   :6379   │                │
│                      │          └───────────┘                │
│                      │          ┌───────────┐                │
│                      ├─────────▶│ RabbitMQ  │                │
│                      │          │ :5672/72  │                │
│                      │          └───────────┘                │
│                      │          ┌───────────┐                │
│                      ├─────────▶│   MinIO   │                │
│                      │          │ :9000/01  │                │
│                      │          └───────────┘                │
│                      │                                       │
│  ┌──────────┐        │        ┌───────────┐   ┌──────────┐  │
│  │  Vite    │        │        │  Reverb   │   │  Worker  │  │
│  │  :5173   │        │        │   :8080   │   │  Queue   │  │
│  └──────────┘        │        └───────────┘   └──────────┘  │
│                      │                                       │
│  ┌──────────┐        │                                       │
│  │ pgAdmin  │────────┘                                       │
│  │  :5050   │                                                │
│  └──────────┘                                                │
└──────────────────────────────────────────────────────────────┘
```

| Serviço | Porta | Descrição |
|---------|-------|-----------|
| **Nginx** | `:80` | Reverse proxy + serve a aplicação |
| **App** | — | PHP 8.4-FPM + Node.js 20 |
| **Vite** | `:5173` | Hot Module Replacement (frontend) |
| **PostgreSQL** | `:5432` | Database principal |
| **Redis** | `:6379` | Cache, sessões, broadcast |
| **RabbitMQ** | `:5672` / `:15672` | Filas / Management UI |
| **MinIO** | `:9000` / `:9001` | Storage S3 / Console |
| **Reverb** | `:8080` | WebSocket server |
| **Queue Worker** | — | Processa jobs em background |
| **pgAdmin** | `:5050` | GUI para PostgreSQL |

---

## Rodando o Projeto

### Pré-requisitos

- [Docker](https://docs.docker.com/get-docker/) + [Docker Compose](https://docs.docker.com/compose/install/)
- [Make](https://www.gnu.org/software/make/) (geralmente já vem instalado)

### Setup com um comando

```bash
git clone https://github.com/FerrazRezende/dockabase.git
cd dockabase
make setup
```

O `make setup` faz tudo automaticamente:

1. Copia `.env.example` para `.env`
2. Sobe todos os containers Docker
3. Instala dependências PHP (`composer install`)
4. Instala dependências JS (`npm install`)
5. Gera a application key (`php artisan key:generate`)
6. Roda as migrations

Após o setup, inicie o Vite para hot-reload do frontend:

```bash
make npm-dev
```

### Acessando os serviços

| Serviço | URL | Credenciais |
|---------|-----|-------------|
| **DockaBase** | http://localhost | — |
| **pgAdmin** | http://localhost:5050 | `admin@dockabase.local` / `secret` |
| **RabbitMQ Management** | http://localhost:15672 | `dockabase` / `secret` |
| **MinIO Console** | http://localhost:9001 | `dockabase` / `secret123456` |

### Setup manual (passo a passo)

Se preferir rodar cada etapa individualmente:

```bash
# 1. Copiar configurações
cp .env.example .env

# 2. Subir containers
make up

# 3. Aguardar serviços ficarem saudáveis (healthchecks automáticos)
# PostgreSQL, Redis e RabbitMQ têm healthchecks configurados

# 4. Instalar dependências
make composer-install
make npm-install

# 5. Configurar Laravel
docker compose exec app php artisan key:generate

# 6. Rodar migrations
make migrate

# 7. Iniciar Vite (em outro terminal ou o container já faz isso)
make npm-dev
```

---

## Comandos Úteis

### Docker

```bash
make up              # Subir containers
make down            # Parar containers
make restart         # Reiniciar containers
make build           # Rebuild sem cache
make rebuild         # Down + Build + Up
make shell           # Acessar shell do container app
make ps              # Listar containers rodando
make clean           # Remover volumes + vendor (reset total)
```

### Database

```bash
make migrate          # Rodar migrations pendentes
make migrate-fresh    # Drop all + migrate + seed (dockabase:migrate-fresh)
make migrate-rollback # Desfazer último batch
make seed             # Rodar seeders
```

### Dependências

```bash
make composer-install # Instalar deps PHP
make composer-update  # Atualizar deps PHP
make npm-install      # Instalar deps Node
make npm-dev          # Iniciar Vite dev server
make npm-build        # Build para produção
```

### Filas (RabbitMQ)

```bash
make queue-work       # Processar jobs
make queue-listen     # Escutar jobs continuamente
make queue-retry      # Retentar jobs falhados
make queue-failed     # Listar jobs falhados
make queue-flush      # Limpar jobs falhados
```

### Logs

```bash
make logs             # Logs do app
make logs-all         # Logs de todos os serviços
make logs-worker      # Logs do queue worker
make logs-reverb      # Logs do WebSocket server
make logs-nginx       # Logs do Nginx
```

### Outros

```bash
make tinker           # REPL do Laravel
make test             # Rodar testes
make artisan cmd=xxx  # Rodar qualquer comando artisan
make clear-all        # Limpar todos os caches
make help             # Listar todos os comandos disponíveis
```

---

## Estrutura do Projeto

```
app/
├── Console/Commands/     # Artisan commands
├── Events/               # Eventos com broadcast
├── Enums/                # Backed enums com métodos
├── Features/             # Feature flags (Pennant class-based)
├── Http/
│   ├── Controllers/      # Controllers (orquestração)
│   ├── Middleware/        # Auth, RLS, Features
│   └── Requests/         # FormRequest (validação)
├── Jobs/                 # Jobs assíncronos
├── Models/               # Eloquent models
├── Notifications/        # Notificações
├── Policies/             # Autorização
├── Providers/            # Service providers
├── Resources/            # API resources (transformação JSON)
├── Services/             # Regras de negócio puras
└── Traits/               # Traits compartilhados

resources/
├── js/
│   ├── Pages/            # Páginas Vue (Inertia)
│   ├── components/       # Componentes Vue
│   ├── composables/      # Composables (hooks Vue)
│   └── types/            # TypeScript types
├── css/                  # Tailwind CSS
└── views/                # Blade templates (mínimo)

database/
├── migrations/           # 31 migrations
├── factories/            # Model factories
└── seeders/              # Seeders

tests/
├── Unit/                 # Services, Enums, Events, Models
└── Feature/              # Controllers, Middleware, Auth
```

---

## Testes

### Rodando os testes

```bash
# Todos os testes
make test

# Diretamente no container
docker compose exec app php artisan test

# Teste específico
docker compose exec app php artisan test tests/Unit/Services/FeatureFlagServiceTest.php
```

### Estrutura de testes

```
tests/
├── Unit/
│   ├── Services/         # FeatureFlagService, CredentialService, etc.
│   ├── Enums/            # Métodos de transformação dos enums
│   ├── Events/           # Broadcast de eventos
│   ├── Models/           # Scopes e relationships
│   ├── Notifications/    # Notificações via canal database
│   └── Policies/         # Regras de autorização
└── Feature/
    ├── Auth/             # Login, registro, verificação
    ├── System/           # Features, databases, credentials
    ├── Middleware/        # Feature middleware
    ├── Profile/          # Gestão de perfil
    └── Lang/             # Validação de chaves de tradução (PT/EN/ES)
```

O projeto segue **TDD** — serviços e lógica de negócio são testados via Unit tests, controllers e fluxos via Feature tests. Traduções são validadas automaticamente por `TranslationKeysTest.php`.

---

## Feature Flags

Features são gerenciadas via **Laravel Pennant** com classes em `app/Features/`. Cada feature tem:

- Classe PHP estendendo `Feature` base com `public string $name`
- Registro no `FeatureServiceProvider` via `Feature::define(NomeFeature::class)`
- Metadata em `config/features.php` (nome, descrição, data de implementação)
- Interface visual no painel admin para ativar/desativar por estratégia

**Estratégias de rollout:** `inactive`, `percentage`, `users`, `all`

---

<div align="center">

**DockaBase** — Projeto em desenvolvimento ativo.

</div>
