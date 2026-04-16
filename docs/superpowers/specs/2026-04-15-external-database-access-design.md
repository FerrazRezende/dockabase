# External Database Access via DBeaver — Design Spec

## Context

Users need to connect to DockaBase-managed databases using external tools like DBeaver, pgAdmin, or any PostgreSQL client. Access must be controlled by credentials — users only see databases and tables their credential grants access to, with read/write permissions enforced at the PostgreSQL level.

## Scope

1. Create PostgreSQL roles (one per credential) when credentials are created/attached to databases
2. Enforce read/write permissions via PostgreSQL GRANT/REVOKE
3. Show connection info (host, port, user, password) on a new "Connection" tab in the credential show page
4. Allow users to regenerate passwords

## 1. Role Lifecycle

### One role per credential

When a credential is created and attached to a database:

| Event | PostgreSQL Action |
|-------|-------------------|
| Credential created | `CREATE ROLE "cred_{id}" LOGIN PASSWORD '{generated}'` |
| Credential attached to database | `GRANT {permission} ON DATABASE "{db}" TO "cred_{id}"` + schema-level grants |
| Credential detached from database | `REVOKE ALL ON DATABASE "{db}" FROM "cred_{id}"` |
| Credential deleted | `DROP ROLE IF EXISTS "cred_{id}"` |
| Credential permission changed | `REVOKE` old grants → `GRANT` new grants on all attached databases |
| Password regenerated | `ALTER ROLE "cred_{id}" PASSWORD '{new}'` |

### Role naming

Pattern: `cred_{credential_id}` — uses the KSUID as suffix (e.g., `cred_2xsTX4xZb5Ph`) ensuring uniqueness.

### Password generation

- 32-character random string, base64-encoded (no special chars that break connection strings)
- Stored hashed in the credential record (`pg_password` column)
- Original password shown only once at creation, then masked

## 2. Permission Mapping

| Credential Permission | PostgreSQL Grants |
|----------------------|-------------------|
| `read` | `GRANT CONNECT ON DATABASE`, `GRANT USAGE ON SCHEMA public`, `GRANT SELECT ON ALL TABLES IN SCHEMA public`, `ALTER DEFAULT PRIVILEGES ... GRANT SELECT ON TABLES` |
| `write` | Same as read + `GRANT INSERT, UPDATE, DELETE ON ALL TABLES`, `GRANT USAGE, CREATE ON SCHEMA public`, `ALTER DEFAULT PRIVILEGES ... GRANT INSERT, UPDATE, DELETE` |
| `read-write` | Same as write (full access) |

Default privilege grants ensure new tables created by the superuser are also accessible by the role.

## 3. Database Schema Change

Add `pg_password` column to credentials table:

```php
Schema::table('credentials', function (Blueprint $table) {
    $table->string('pg_password', 255)->nullable()->after('created_by');
});
```

The password is stored hashed (bcrypt) so it can be verified but the plaintext is never stored long-term.

**Important:** Since users need the plaintext to connect, the password is shown once at creation and can be regenerated. It's stored encrypted (not hashed) so it can be displayed when needed — using `Crypt::encrypt()` / `Crypt::decrypt()`.

## 4. Credential Show — "Connection" Tab

Add a new tab to the credential show page using the same PvTabs pattern:

| Tab | Content |
|-----|---------|
| **Informações** | Current content (details, users, databases, metadata) |
| **Conexão** | Connection info card + regenerate password button |

### Connection info card shows:

| Field | Value | Copy button |
|-------|-------|-------------|
| Host | Database host (from attached database) | Yes |
| Port | Database port | Yes |
| Database | Database name | Yes |
| Usuário | `cred_{credential_id}` | Yes |
| Senha | `••••••••` (masked, com botão olho) | Yes |

**Multiple databases:** If the credential is attached to multiple databases, show a dropdown to select which database's connection info to display. Username and password are the same for all; host/port/database_name change.

### Password reveal flow

The database password is always masked (`••••••••`). When the user clicks the eye icon:

1. A dialog opens asking the user to type **their own login password** (the password they use to log into DockaBase)
2. The typed password is sent to a new endpoint `POST /app/credentials/{credential}/reveal-password`
3. Backend verifies the password matches `Auth::user()->password` via `Hash::check()`
4. If correct, returns the decrypted `pg_password`
5. Frontend shows the password for 30 seconds, then auto-hides back to `••••••••`

This happens **every time** the user clicks the eye icon — no session caching.

### Regenerate password button

- In the connection card, below the password field
- ConfirmDialog before regenerating
- On confirm: generates new password, updates `pg_password`, runs `ALTER ROLE`, shows the new password once

## 5. Backend — DatabaseAccessService

New service to manage PostgreSQL roles and permissions:

```
createRole(Credential $credential): string        — CREATE ROLE, returns generated password
dropRole(Credential $credential): void             — DROP ROLE
grantAccess(Credential $credential, Database $db)  — GRANT permissions on database
revokeAccess(Credential $credential, Database $db) — REVOKE permissions
updatePermissions(Credential $credential): void    — REVOKE + re-GRANT on all attached databases
regeneratePassword(Credential $credential): string — ALTER ROLE PASSWORD
```

All methods use `DB::connection('pgsql')->statement()` to execute DDL.

### Integration points

| Controller method | Service call |
|-------------------|-------------|
| `CredentialController@store` | `createRole()` |
| `CredentialController@destroy` | `dropRole()` |
| `CredentialController@update` (permission change) | `updatePermissions()` |
| `DatabaseController@attachCredential` | `grantAccess()` |
| `DatabaseController@detachCredential` | `revokeAccess()` |
| `CreateDatabaseJob` (on success) | `grantAccess()` for all attached credentials |

## 6. Connection Info API

Add to `CredentialController@show` or a new endpoint:

The credential show page passes connection info as props. For the selected database:

```json
{
  "host": "localhost",
  "port": 5432,
  "database_name": "dockabase_mydb",
  "username": "cred_2xsTX4xZb5Ph",
  "password": "decrypted_password_or_null"
}
```

Password is decrypted only when explicitly requested (regenerate or show action).

### Reveal password endpoint

`POST /app/credentials/{credential}/reveal-password`

- Request body: `{ password: "user_login_password" }`
- Validates: user owns credential (is member or creator), password matches `Auth::user()`
- Returns: `{ pg_password: "decrypted_password" }`

## 7. Files to Create/Modify

| File | Action |
|------|--------|
| `app/Services/DatabaseAccessService.php` | New — role/permission management |
| `database/migrations/*_add_pg_password_to_credentials.php` | New |
| `app/Http/Controllers/App/CredentialController.php` | Add role lifecycle calls, connection info |
| `app/Http/Controllers/App/DatabaseController.php` | Add grant/revoke calls |
| `app/Jobs/CreateDatabaseJob.php` | Grant access on creation |
| `resources/js/Pages/App/Credentials/Show.vue` | Add tabs, connection tab |
| `lang/pt.json`, `lang/en.json`, `lang/es.json` | New keys |

## 8. Verification

1. Create a credential → verify PostgreSQL role exists (`\du` in psql)
2. Attach credential to database → verify grants (`\dp` in psql)
3. Open credential show → "Conexão" tab shows connection info
4. Copy connection info into DBeaver → connect succeeds
5. With `read` credential → can SELECT but not INSERT
6. With `read-write` credential → can INSERT, UPDATE, DELETE
7. Detach credential from database → DBeaver connection loses access
8. Delete credential → role is dropped
9. Regenerate password → old password stops working, new one works
