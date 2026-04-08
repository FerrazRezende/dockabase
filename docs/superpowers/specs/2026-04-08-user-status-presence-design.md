# User Status & Presence System - Design Specification

**Date:** 2026-04-08
**Status:** Approved
**Priority:** P1 (High)

---

## Overview

Sistema de presença e status para usuários do DockaBase com arquitetura híbrida Redis + MySQL. O sistema permite que usuários definam seu status manualmente (Online, Ausente, Ocupado, Offline) enquanto o sistema gerencia automaticamente a detecção de entrada/saída.

---

## Architecture: Hybrid Redis + MySQL

```
┌─────────────────────────────────────────────────────────────────────────┐
│                         USER STATUS FLOW                                │
├─────────────────────────────────────────────────────────────────────────┤
│                                                                          │
│  ┌────────────┐    ┌────────────┐    ┌────────────┐    ┌────────────┐  │
│  │   LOGIN    │───▶│   Redis    │───▶│    Echo    │───▶│   Admin    │  │
│  │  Auto:     │    │  Presence  │    │  Broadcast │    │   View     │  │
│  │  Online    │    │  (temp)    │    │  Real-time │    │  Updates   │  │
│  └────────────┘    └─────┬──────┘    └────────────┘    └────────────┘  │
│                          │                                              │
│                          │                                              │
│                          ▼                                              │
│                   ┌────────────┐                                        │
│                   │   MySQL    │                                        │
│                   │ Activities │                                        │
│                   │ (History)  │                                        │
│                   └────────────┘                                        │
│        (Only manual changes + important events)                         │
└─────────────────────────────────────────────────────────────────────────┘
```

---

## Status Enum

```php
// app/Enums/UserStatusEnum.php

enum UserStatusEnum: string
{
    case ONLINE = 'online';       // Green - Auto on login
    case AWAY = 'away';           // Yellow - Manual
    case BUSY = 'busy';           // Red - Manual
    case OFFLINE = 'offline';     // Gray - Auto on logout
}
```

**Colors & Labels:**

| Status | Value | Color | PT | EN | ES |
|--------|-------|-------|----|----|-----|
| Online | `online` | `#22c55e` (green) | Online | Online | En línea |
| Ausente | `away` | `#eab308` (yellow) | Ausente | Away | Ausente |
| Ocupado | `busy` | `#ef4444` (red) | Ocupado | Busy | Ocupado |
| Offline | `offline` | `#6b7280` (gray) | Offline | Offline | Desconectado |

---

## Data Structures

### Redis (Real-time Presence)

```
Key: user:{user_id}:status
Value: {"status":"online","updated_at":"2026-04-08T10:30:00Z"}
TTL: 300 seconds (5 minutes)

Key: user:{user_id}:heartbeat
Value: "2026-04-08T10:30:00Z"
TTL: 120 seconds (2 minutes)
```

**Behavior:**
- Set on login (auto online)
- Updated on manual change
- Deleted on logout (auto offline)
- Heartbeat every 2 minutes via middleware

### MySQL (Persistent History)

```sql
CREATE TABLE user_activities (
    id CHAR(16) PRIMARY KEY,           -- KSUID
    user_id CHAR(16) NOT NULL,
    activity_type ENUM(
        'status_changed',              -- Manual status change ONLY
        'database_created',            -- User created database
        'credential_created'           -- User created credential
    ) NOT NULL,
    from_status VARCHAR(20) NULL,      -- Previous status (for status_changed)
    to_status VARCHAR(20) NULL,        -- New status (for status_changed)
    metadata JSON NULL,                -- {database_name: "dev", permission: "read-write"}
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_user_created (user_id, created_at DESC),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**What gets saved to MySQL:**
- ✅ Manual status changes (Online → Ausente, etc.)
- ✅ Database created events
- ✅ Credential created events
- ❌ Auto login (Online)
- ❌ Auto logout (Offline)

---

## Components

### Backend Components

| Component | Path | Responsibility |
|-----------|------|-----------------|
| `UserStatusEnum` | `app/Enums/UserStatusEnum.php` | Status enum with labels/colors |
| `UserStatusService` | `app/Services/UserStatusService.php` | Manage status (get/set/heartbeat) |
| `UserActivityService` | `app/Services/UserActivityService.php` | Log activities to MySQL |
| `UserStatusUpdated` | `app/Events/UserStatusUpdated.php` | Broadcast status change via Echo |
| `UserActivityLogged` | `app/Events/UserActivityLogged.php` | Broadcast activity log via Echo |
| `UserStatusMiddleware` | `app/Middleware/TrackUserStatus.php` | Auto online + heartbeat |
| `UserStatusController` | `app/Http/Controllers/UserStatusController.php` | API endpoints for status |
| `UserActivityController` | `app/Http/Controllers/UserActivityController.php` | API endpoints for activities |
| `LogoutListener` | `app/Listeners/HandleUserLogout.php` | Auto offline on logout |
| `DatabaseCreatedListener` | `app/Listeners/LogDatabaseCreated.php` | Log database creation |
| `CredentialCreatedListener` | `app/Listeners/LogCredentialCreated.php` | Log credential creation |
| `User` | `app/Models/User.php` | Add status relationship |

### Frontend Components

| Component | Path | Responsibility |
|-----------|------|-----------------|
| `StatusPickerDropdown.vue` | `resources/js/components/StatusPickerDropdown.vue` | Status selector button with dropdown |
| `UserAvatarWithStatus.vue` | `resources/js/components/UserAvatarWithStatus.vue` | Avatar with colored status border |
| `UserActivityTimeline.vue` | `resources/js/components/UserActivityTimeline.vue` | Timeline of user activities |
| `useUserStatus.ts` | `resources/js/composables/useUserStatus.ts` | Composable for status management |
| `useEchoChannels.ts` | `resources/js/composables/useEchoChannels.ts` | Echo channel listeners |

---

## Workflows

### 1. Login → Auto Online

```
User Login
    │
    ▼
UserStatusMiddleware (on authenticated request)
    │
    ├── UserStatusService::setOnline($user)
    │       │
    │       ├── Redis: SET user:{id}:status = {"status":"online",...}
    │       ├── Redis: SET user:{id}:heartbeat = now()
    │       └── Broadcast: UserStatusUpdated (to admin channel)
    │
    └── Continue request
```

**NO MySQL entry** (automatic action).

### 2. Logout → Auto Offline

```
User Logout
    │
    ▼
LogoutListener (Auth::logout event)
    │
    ├── UserStatusService::setOffline($user)
    │       │
    │       ├── Redis: DEL user:{id}:*
    │       └── Broadcast: UserStatusUpdated (to admin channel)
    │
    └── Complete logout
```

**NO MySQL entry** (automatic action).

### 3. Manual Status Change → Saves to MySQL

```
User clicks "Ausente" in dropdown
    │
    ▼
POST /user/status (UserStatusController@update)
    │
    ├── UserStatusService::setStatus($user, 'away')
    │       │
    │       ├── Get current status: 'online'
    │       ├── Redis: SET user:{id}:status = {"status":"away",...}
    │       └── Return: ['from' => 'online', 'to' => 'away']
    │
    ├── UserActivityService::logStatusChange($user, 'online', 'away')
    │       │
    │       └── MySQL: INSERT user_activities (status_changed)
    │
    └── Broadcast: UserStatusUpdated (to admin channel)
```

**SAVES to MySQL** (manual action).

### 4. Database Created → Saves to MySQL

```
User creates database "production"
    │
    ▼
DatabaseCreated Event (already exists)
    │
    ▼
DatabaseCreatedListener@handle
    │
    ├── UserActivityService::logDatabaseCreated($user, $database)
    │       │
    │       └── MySQL: INSERT user_activities (database_created)
    │           metadata: {name: "production", permission: "read-write"}
    │
    └── Broadcast: UserActivityLogged (to admin channel)
```

**SAVES to MySQL** (important event).

### 5. Heartbeat (Every 2 minutes)

```
Authenticated Request (any)
    │
    ▼
UserStatusMiddleware
    │
    ├── Check: Redis GET user:{id}:heartbeat
    │
    ├── If expired (> 2 min) OR not exists:
    │   │
    │   └── UserStatusService::refreshHeartbeat($user)
    │           │
    │           ├── Redis: SET user:{id}:heartbeat = now()
    │           └── If status was offline, set back to online
    │
    └── Continue request
```

---

## UI Design

### 1. Status Picker Button (Sidebar)

**Location:** Above the settings button in sidebar

```
┌──────────────────────────────────────┐
│  [🟢] Online        ⚙️  ← Settings   │
│   ▼                                   │
│   ┌────────────────────────────────┐ │
│   │ ● Online      (Verde)          │ │
│   │ ● Ausente     (Amarelo)        │ │
│   │ ● Ocupado     (Vermelho)       │ │
│   │ ● Offline     (Cinza)          │ │
│   └────────────────────────────────┘ │
└──────────────────────────────────────┘
```

**Behavior:**
- Hover shows dropdown
- Click sets status immediately
- Current status highlighted
- Echo listener updates button when changed elsewhere

### 2. User List (Admin) - Index.vue

**Location:** `/system/users` table

```
┌────────────────────────────────────────────────────────┐
│ Name              Email         Roles        Status    │
├────────────────────────────────────────────────────────┤
│ ┌─────┐ João Silva   joao@...      Admin     🟢       │
│ │  🟢 │ │                                       ↑       │
│ └─────┘ │                                   Border     │
│ Avatar   │                                   color      │
│          │                                   2px solid  │
└────────────────────────────────────────────────────────┘
```

**Changes:**
- Add avatar column before name
- Avatar has 2px solid border in status color
- Status indicator dot next to active/inactive badge
- Echo listener updates in real-time

### 3. User Show Page - Show.vue

**New Tab:** "Atualizações" (between "Information" and "Roles")

```
┌─────────────────────────────────────────────────────────────┐
│  [Information] [Updates] [Roles and Permissions]           │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ACTIVITY TIMELINE                                          │
│  ────────────────────────                                   │
│                                                             │
│  ● Today, 14:30                                             │
│    Status changed: Online → Ausente                         │
│    ┌───────────────────────────────────────────────────┐   │
│    │                             [🟡] Ausente          │   │
│    └───────────────────────────────────────────────────┘   │
│                                                             │
│  ● Today, 10:15                                             │
│    Database created: "production"                           │
│    ┌───────────────────────────────────────────────────┐   │
│    │  🗄️  production (read-write)                      │   │
│    └───────────────────────────────────────────────────┘   │
│                                                             │
│  ● Yesterday, 18:45                                         │
│    Status changed: Online → Ocupado                         │
│    ┌───────────────────────────────────────────────────┐   │
│    │                             [🔴] Ocupado          │   │
│    └───────────────────────────────────────────────────┘   │
│                                                             │
│  ● Yesterday, 09:00                                         │
│    Credential created: "Dev Team"                           │
│    ┌───────────────────────────────────────────────────┐   │
│    │  🔑 Dev Team (read-write)                         │   │
│    └───────────────────────────────────────────────────┘   │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- Paginated (20 per page)
- Most recent first
- Real-time updates via Echo
- Icons for each activity type

---

## Permissions

| Action | Permission | Notes |
|--------|------------|-------|
| View others' status | `super-admin` only | Only godlike admin sees status |
| Change own status | All authenticated | Any user can set their status |
| View activity timeline | `super-admin` only | Only godlike admin sees timeline |
| View status button in sidebar | All authenticated | Every user sees their own status |

---

## API Endpoints

### Status Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/user/status` | Get current user's status | Required |
| PUT | `/api/user/status` | Set current user's status | Required |
| GET | `/api/user/{id}/status` | Get any user's status (admin only) | Admin |

### Activity Endpoints

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/user/{id}/activities` | Get user activity timeline | Admin |

---

## Echo Channels

### Private Channel: `users.{id}`

**Events:**

| Event | Payload | When |
|-------|---------|------|
| `UserStatusUpdated` | `{user_id, status, previous_status}` | Status changes (any) |
| `UserActivityLogged` | `{user_id, activity_type, ...}` | Activity logged to MySQL |

**Who subscribes:**
- Super-admins subscribe to all users
- Users subscribe to themselves

---

## Translations

### Status Labels

| Key | PT | EN | ES |
|-----|----|----|-----|
| Online | Online | Online | En línea |
| Ausente | Ausente | Away | Ausente |
| Ocupado | Ocupado | Busy | Ocupado |
| Offline | Offline | Offline | Desconectado |

### UI Elements

| Key | PT | EN | ES |
|-----|----|----|-----|
| Status | Status | Status | Estado |
| Set your status | Definir seu status | Set your status | Define tu estado |
| Current status | Status atual | Current status | Estado actual |
| Updates | Atualizações | Updates | Actualizaciones |
| Activity Timeline | Linha do tempo de atividades | Activity Timeline | Línea de tiempo de actividad |
| No recent activity | Sem atividade recente | No recent activity | Sin actividad reciente |
| Status changed | Status alterado | Status changed | Estado alterado |
| Database created | Database criado | Database created | Base de datos creada |
| Credential created | Credencial criada | Credential created | Credencial creada |
| Changed from | Alterado de | Changed from | Cambiado de |
| to | para | to | a |
| View activity | Ver atividade | View activity | Ver actividad |

### Messages

| Key | PT | EN | ES |
|-----|----|----|-----|
| Status updated successfully | Status atualizado com sucesso | Status updated successfully | Estado actualizado correctamente |
| Failed to update status | Falha ao atualizar status | Failed to update status | Error al actualizar estado |

---

## File Structure

```
app/
├── Enums/
│   └── UserStatusEnum.php (NEW)
├── Services/
│   ├── UserStatusService.php (NEW)
│   └── UserActivityService.php (NEW)
├── Events/
│   ├── UserStatusUpdated.php (NEW)
│   └── UserActivityLogged.php (NEW)
├── Listeners/
│   ├── HandleUserLogout.php (NEW)
│   ├── LogDatabaseCreated.php (NEW)
│   └── LogCredentialCreated.php (NEW)
├── Http/
│   ├── Controllers/
│   │   ├── UserStatusController.php (NEW)
│   │   └── UserActivityController.php (NEW)
│   └── Middleware/
│       └── TrackUserStatus.php (NEW)
└── Models/
    ├── User.php (MODIFY - add relationship)
    └── UserActivity.php (NEW)

resources/js/
├── components/
│   ├── StatusPickerDropdown.vue (NEW)
│   ├── UserAvatarWithStatus.vue (NEW)
│   └── UserActivityTimeline.vue (NEW)
└── composables/
    ├── useUserStatus.ts (NEW)
    └── useEchoChannels.ts (NEW)

database/
└── migrations/
    └── 2026_04_08_000001_create_user_activities_table.php (NEW)

lang/
├── pt.json (MODIFY - add translations)
├── en.json (MODIFY - add translations)
└── es.json (MODIFY - add translations)
```

---

## Testing Strategy

### Unit Tests (70-80% coverage)

**Services:**
- `UserStatusServiceTest` - get/set/heartbeat logic
- `UserActivityServiceTest` - logging activities

**Enums:**
- `UserStatusEnumTest` - label(), color(), methods

### Feature Tests

**API:**
- Status endpoints (get/set)
- Activity endpoints (pagination)
- Permissions (admin-only access)

**Real-time:**
- Echo broadcasting
- Channel authorization

### Browser Tests (optional)

- Status picker interaction
- Real-time updates

---

## Implementation Checklist

- [ ] Create `UserStatusEnum` with labels/colors
- [ ] Create `user_activities` migration
- [ ] Create `UserActivity` model
- [ ] Create `UserStatusService` (Redis operations)
- [ ] Create `UserActivityService` (MySQL operations)
- [ ] Create Echo events (`UserStatusUpdated`, `UserActivityLogged`)
- [ ] Create `UserStatusMiddleware` (auto online + heartbeat)
- [ ] Create `LogoutListener` (auto offline)
- [ ] Create `DatabaseCreatedListener` (log database)
- [ ] Create `CredentialCreatedListener` (log credential)
- [ ] Create API endpoints (status + activities)
- [ ] Create frontend components (StatusPickerDropdown, UserAvatarWithStatus, UserActivityTimeline)
- [ ] Create composables (useUserStatus, useEchoChannels)
- [ ] Update Users/Index.vue (avatar with status border)
- [ ] Update Users/Show.vue (add Updates tab)
- [ ] Add translations (PT, EN, ES)
- [ ] Write tests (unit + feature)
- [ ] Manual testing (real-time updates)

---

## Success Criteria

1. ✅ User goes auto-online on login
2. ✅ User goes auto-offline on logout
3. ✅ User can manually set status (Online/Ausente/Ocupado/Offline)
4. ✅ Admin sees real-time status in user list
5. ✅ Admin sees activity timeline on user profile
6. ✅ Manual status changes are logged to MySQL
7. ✅ Database/credential creation is logged to MySQL
8. ✅ Real-time updates via Laravel Echo
9. ✅ All UI text translated (PT, EN, ES)
10. ✅ Only super-admin can see status/timeline

---

**Next Step:** Invoke `superpowers:writing-plans` to create implementation plan with TDD + multiagent approach.
