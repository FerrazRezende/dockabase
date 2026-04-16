# Database Tabs + PSQL Console — Design Spec

## Context

The database show page currently has no tabs and no way to interact with the database. Users need a real PSQL terminal to run queries, inspect schemas, and manage data directly from the platform.

## Scope

1. Add tabs to the database show page (using existing PvTabs pattern from Users/Show)
2. Add a "Open Console" button in the tabs header
3. Create a full-page PSQL console that opens in a new browser tab

## 1. Database Show — Tabs

**File:** `resources/js/Pages/App/Databases/Show.vue`

Refactor the current flat layout into a PvTabs structure with one tab:

| Tab | Value | Content |
|-----|-------|---------|
| Informações | `info` | Everything currently on the page (timeline, alerts, cards, credentials, metadata) |

The tabs header area uses `justify-between` — tabs on the left, **"Open Console"** button on the right. Button opens `/app/databases/{database}/console` in a new browser tab (`target="_blank"`). Only shown when database status is `ready`.

**Pattern:** Same PvTabs + PvTabsContent used in `resources/js/Pages/System/Users/Show.vue`.

## 2. Console Page

**Route:** `GET /app/databases/{database}/console`
**Controller:** `DatabaseConsoleController::console()`
**Authorization:** `DatabasePolicy@view` + user must have a credential attached to this database

**Layout:** Full page with:
- App sidebar (same as rest of the app)
- Header bar with: back button (→ database show), database name, dark mode toggle
- Console area: xterm.js terminal filling remaining viewport height

**Page file:** `resources/js/Pages/App/Databases/Console.vue`

## 3. Backend — PSQL Process Manager

### DatabaseConsoleController

```
GET  /app/databases/{database}/console          → console()     → renders page
POST /app/databases/{database}/console/start    → start()       → spawns psql process, returns session ID
POST /app/databases/{database}/console/input    → input()       → sends stdin to psql
```

### Process lifecycle

1. User opens console page → frontend calls `start()` endpoint
2. `start()` spawns `psql` via `proc_open()`:
   - Command: `psql -h {host} -p {port} -U {user} -d {database_name}`
   - Uses a dedicated PostgreSQL user per credential or a shared user with restricted permissions
   - Stores process handles in a process pool (in-memory map, keyed by session ID)
3. A background loop reads stdout/stderr and broadcasts via Laravel Echo
4. Frontend sends keystrokes/commands via `input()` endpoint → writes to stdin
5. On page close/disconnect → process is killed after timeout (30s inactivity)

### Session management

- Store active processes in a service: `DatabaseConsoleService`
- Each session has a unique ID, linked to user + database
- Timeout: kill process after 30 seconds of no input
- Max sessions per user: 1 (opening console again reuses or kills old session)

## 4. Real-time Communication

### Channel

`private-database-console.{database_id}.{user_id}`

### Events

| Event | Direction | Payload |
|-------|-----------|---------|
| `console.output` | Server → Client | `{ sessionId, data }` — stdout/stderr from psql |
| `console.started` | Server → Client | `{ sessionId }` — process spawned successfully |
| `console.ended` | Server → Client | `{ sessionId, reason }` — process exited |

Input is sent via HTTP POST (simpler than bidirectional WebSocket), output is broadcast via Echo.

## 5. Frontend — xterm.js

### Dependencies

- `xterm` (MIT, ~200KB) — terminal emulator
- `xterm-addon-fit` — auto-resize to container
- `@xterm/xterm` (preferred) or `xterm` package

### Console.vue behavior

1. On mount: subscribe to Echo channel, call `start()` endpoint
2. On `console.output` event: write data to xterm terminal
3. On xterm `onData` (user types): POST to `input()` endpoint
4. On `console.ended` event: show "Session ended" message
5. On unmount: unsubscribe Echo, optionally call stop endpoint

### Theme

Match app theme:
- Dark mode: dark background (#1e1e2e), green text (#a6e3a1)
- Light mode: white background, dark text

## 6. Security

- **Authorization**: Policy checks user has credential attached to database
- **Read-only enforcement**: If credential permission is `read`, start psql with `--variable=ON_ERROR_STOP=1` and set `default_transaction_read_only = on` via PGOPTIONS
- **Process isolation**: Each psql runs as the DockaBase PostgreSQL user with restricted permissions per credential
- **Rate limiting**: Max 1 concurrent console session per user
- **Timeout**: 30s inactivity kills the process

## Files to Create/Modify

| File | Action |
|------|--------|
| `app/Http/Controllers/App/DatabaseConsoleController.php` | New |
| `app/Services/DatabaseConsoleService.php` | New |
| `app/Events/ConsoleOutput.php` | New |
| `app/Events/ConsoleStarted.php` | New |
| `app/Events/ConsoleEnded.php` | New |
| `routes/app.php` | Add console routes |
| `resources/js/Pages/App/Databases/Show.vue` | Refactor to tabs |
| `resources/js/Pages/App/Databases/Console.vue` | New |
| `resources/js/components/DatabaseConsole.vue` | New (xterm wrapper) |
| `lang/pt.json`, `lang/en.json`, `lang/es.json` | New keys |

## Verification

1. Open database show → see "Informações" tab with existing content
2. Click "Open Console" → new tab opens with sidebar + terminal
3. Type `\dt` → see list of tables
4. Type `SELECT 1;` → see result
5. Close tab → process is cleaned up after timeout
6. User with read-only credential → cannot INSERT/UPDATE/DELETE
