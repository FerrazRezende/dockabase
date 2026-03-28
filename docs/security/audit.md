# Security Audit Template

> Complete this audit after each feature implementation or before each release.

---

# Security Audit Report

## Metadata

| Field | Value |
|-------|-------|
| Feature/Release | [Feature name or version] |
| Audit Date | YYYY-MM-DD |
| Auditor | [Name] |
| Status | Pass / Fail / Needs Review |

---

## 1. Authentication & Authorization

### 1.1 Authentication

| Check | Status | Notes |
|-------|--------|-------|
| All protected routes require authentication | ⬜ | |
| Correct guard used (`web` vs `api`) | ⬜ | |
| Session management secure | ⬜ | |
| Token expiration implemented | ⬜ | |
| OTP codes have expiration (5-15 min) | ⬜ | |
| Rate limiting on auth endpoints | ⬜ | |

**Issues Found:**
- [Issue description and remediation]

### 1.2 Authorization (RBAC)

| Check | Status | Notes |
|-------|--------|-------|
| Policies implemented for all resources | ⬜ | |
| `$user->can()` / `$user->hasPermissionTo()` used | ⬜ | |
| Multi-tenant isolation enforced | ⬜ | |
| Super-admin bypass documented | ⬜ | |

**Permissions Matrix:**

| Resource | Select | Insert | Update | Delete |
|----------|--------|--------|--------|--------|
| Table A | ✅ | ✅ | ⬜ | ⬜ |
| Table B | ✅ | ⬜ | ⬜ | ⬜ |

**Issues Found:**
- [Issue description and remediation]

### 1.3 Row Level Security (RLS)

| Check | Status | Notes |
|-------|--------|-------|
| RLS enabled on tenant tables | ⬜ | |
| PostgreSQL context set correctly | ⬜ | |
| RLS policies tested | ⬜ | |
| Admin bypass works correctly | ⬜ | |

**Issues Found:**
- [Issue description and remediation]

---

## 2. Input Validation & Sanitization

### 2.1 Validation

| Check | Status | Notes |
|-------|--------|-------|
| FormRequest used for all inputs | ⬜ | |
| Type validation enforced | ⬜ | |
| Length limits enforced | ⬜ | |
| Array/object validation | ⬜ | |
| File upload validation | ⬜ | |

**Validation Rules Review:**
```php
// Example of reviewed validation rules
'rules' => [
    'name' => ['required', 'string', 'max:255'],
    'email' => ['required', 'email', 'unique:users'],
]
```

### 2.2 OWASP Top 10

| Vulnerability | Status | Notes |
|---------------|--------|-------|
| **Injection (SQL)** | ⬜ | Eloquent parameter binding used |
| **XSS** | ⬜ | Output escaping in place |
| **CSRF** | ⬜ | Token verification enabled |
| **Broken Auth** | ⬜ | Reviewed in section 1 |
| **Broken Access Control** | ⬜ | Reviewed in section 1.2 |
| **Security Misconfiguration** | ⬜ | |
| **Sensitive Data Exposure** | ⬜ | |
| **XML External Entities** | ⬜ | N/A if no XML parsing |
| **Broken Deserialization** | ⬜ | |
| **Insufficient Logging** | ⬜ | |

**Issues Found:**
- [Issue description and remediation]

---

## 3. Data Protection

### 3.1 Encryption

| Check | Status | Notes |
|-------|--------|-------|
| Sensitive data encrypted at rest | ⬜ | |
| TLS for data in transit | ⬜ | |
| Encryption keys rotated | ⬜ | |
| pgcrypto used where applicable | ⬜ | |

### 3.2 PII Handling

| Check | Status | Notes |
|-------|--------|-------|
| PII fields identified | ⬜ | |
| PII masked in logs | ⬜ | |
| PII not exposed in API responses | ⬜ | |
| GDPR compliance considered | ⬜ | |

**PII Fields Identified:**
- [List of fields containing PII]

**Issues Found:**
- [Issue description and remediation]

---

## 4. API Security

### 4.1 Dynamic API

| Check | Status | Notes |
|-------|--------|-------|
| Query parameter sanitization | ⬜ | |
| SQL injection prevention | ⬜ | |
| Column whitelist enforced | ⬜ | |
| Rate limiting implemented | ⬜ | |
| Response size limits | ⬜ | |

### 4.2 Query Syntax Security

| Operator | Risk Level | Mitigation |
|----------|------------|------------|
| `eq` | Low | Parameter binding |
| `like` | Medium | Input sanitization |
| `in` | Medium | Array size limit |
| `gte/lte` | Low | Parameter binding |

**Issues Found:**
- [Issue description and remediation]

---

## 5. Storage Security

### 5.1 File Uploads

| Check | Status | Notes |
|-------|--------|-------|
| File type whitelist | ⬜ | |
| File size limits | ⬜ | |
| Virus scanning | ⬜ | |
| Random filename generation | ⬜ | |
| Storage outside webroot | ⬜ | |

### 5.2 Access Control

| Check | Status | Notes |
|-------|--------|-------|
| Signed URLs for private files | ⬜ | |
| URL expiration implemented | ⬜ | |
| Bucket policies configured | ⬜ | |

**Issues Found:**
- [Issue description and remediation]

---

## 6. Realtime Security

### 6.1 WebSocket Authentication

| Check | Status | Notes |
|-------|--------|-------|
| Channel authentication | ⬜ | |
| User presence validation | ⬜ | |
| Channel subscription limits | ⬜ | |

### 6.2 PostgreSQL LISTEN/NOTIFY

| Check | Status | Notes |
|-------|--------|-------|
| Trigger security | ⬜ | |
| Payload sanitization | ⬜ | |
| Channel access control | ⬜ | |

**Issues Found:**
- [Issue description and remediation]

---

## 7. Infrastructure Security

### 7.1 Configuration

| Check | Status | Notes |
|-------|--------|-------|
| APP_ENV not 'local' in production | ⬜ | |
| APP_KEY set | ⬜ | |
| Debug mode disabled | ⬜ | |
| Secure cookies enabled | ⬜ | |
| CORS configured correctly | ⬜ | |

### 7.2 Dependencies

| Check | Status | Notes |
|-------|--------|-------|
| `composer audit` passed | ⬜ | |
| `npm audit` passed | ⬜ | |
| No known vulnerabilities | ⬜ | |

**Dependency Audit Results:**
```
[Paste audit output here]
```

---

## 8. Logging & Monitoring

### 8.1 Security Events

| Event | Logged | Alert |
|-------|--------|-------|
| Failed login attempts | ⬜ | ⬜ |
| Permission denied | ⬜ | ⬜ |
| Rate limit exceeded | ⬜ | ⬜ |
| Suspicious input detected | ⬜ | ⬜ |

### 8.2 Log Security

| Check | Status | Notes |
|-------|--------|-------|
| No sensitive data in logs | ⬜ | |
| Log rotation configured | ⬜ | |
| Log access restricted | ⬜ | |

---

## 9. Test Coverage

### 9.1 Security Tests

| Test Type | Coverage | Notes |
|-----------|----------|-------|
| Auth bypass tests | ⬜ | |
| Permission boundary tests | ⬜ | |
| Input validation tests | ⬜ | |
| RLS isolation tests | ⬜ | |

### 9.2 Penetration Testing

| Check | Status | Notes |
|-------|--------|-------|
| SQL injection tested | ⬜ | |
| XSS tested | ⬜ | |
| CSRF tested | ⬜ | |
| IDOR tested | ⬜ | |

---

## 10. Summary

### Critical Issues
| Issue | Severity | Status | Owner |
|-------|----------|--------|-------|
| [Issue] | Critical | Open | [Name] |

### High Issues
| Issue | Severity | Status | Owner |
|-------|----------|--------|-------|
| [Issue] | High | Open | [Name] |

### Medium Issues
| Issue | Severity | Status | Owner |
|-------|----------|--------|-------|
| [Issue] | Medium | Open | [Name] |

### Low Issues
| Issue | Severity | Status | Owner |
|-------|----------|--------|-------|
| [Issue] | Low | Open | [Name] |

---

## Sign-off

| Role | Name | Date | Signature |
|------|------|------|-----------|
| Developer | | | |
| Security Reviewer | | | |
| Tech Lead | | | |

---

## Changelog

| Date | Version | Changes |
|------|---------|---------|
| YYYY-MM-DD | 1.0 | Initial audit |
