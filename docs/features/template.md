# Feature Template

> Use this template for each new feature. Copy to `docs/features/{feature-name}.md`

---

# [Feature Name]

## Metadata

| Field | Value |
|-------|-------|
| Status | Draft / In Progress / Review / Done |
| Priority | P0 (Critical) / P1 (High) / P2 (Medium) / P3 (Low) |
| Phase | 1-6 (see CLAUDE.md) |
| Feature Flag | `feature-name` (if applicable) |
| Dependencies | List of dependent features |

## Overview

### Problem Statement
Describe the problem this feature solves. Why is it needed?

### Proposed Solution
High-level description of the solution approach.

### Goals
- Goal 1
- Goal 2
- Goal 3

### Non-Goals
- What this feature will NOT do
- Scope boundaries

---

## User Stories

### US-01: [Story Title]

**As a** [type of user]
**I want to** [action]
**So that** [benefit/value]

#### Acceptance Criteria
```gherkin
Scenario: [Scenario name]
  Given [precondition]
  When [action]
  Then [expected result]
```

#### Technical Notes
- Implementation details
- Database changes required
- API endpoints affected

---

### US-02: [Story Title]

(Repeat pattern for each user story)

---

## Technical Design

### Architecture

```
┌─────────────┐
│   Client    │
└──────┬──────┘
       │
┌──────▼──────┐
│ Controller  │
└──────┬──────┘
       │
┌──────▼──────┐
│  Service    │
└──────┬──────┘
       │
┌──────▼──────┐
│   Model     │
└─────────────┘
```

### Database Schema

```sql
-- Migration description
CREATE TABLE example (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT NOW()
);
```

### API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/resource` | List resources |
| POST | `/api/v1/resource` | Create resource |
| GET | `/api/v1/resource/{id}` | Get single resource |
| PATCH | `/api/v1/resource/{id}` | Update resource |
| DELETE | `/api/v1/resource/{id}` | Delete resource |

### Files to Create/Modify

```
app/
├── Http/
│   ├── Controllers/
│   │   └── ExampleController.php
│   └── Requests/
│       └── ExampleRequest.php
├── Models/
│   └── Example.php
├── Services/
│   └── ExampleService.php
├── Policies/
│   └── ExamplePolicy.php
└── Resources/
    └── ExampleResource.php

database/
├── migrations/
│   └── create_examples_table.php
└── seeders/
    └── ExampleSeeder.php

tests/
├── Unit/Domain/
│   └── ExampleServiceTest.php
└── Feature/
    └── ExampleControllerTest.php
```

---

## Security Considerations

### Authentication
- [ ] Requires authentication
- [ ] Guard type: `web` / `api`

### Authorization
- [ ] Policy required
- [ ] Permissions: `resource.select`, `resource.insert`, `resource.update`, `resource.delete`

### RLS (Row Level Security)
- [ ] RLS policy required
- [ ] Scope: Project / User

### Input Validation
- [ ] FormRequest validation rules defined
- [ ] Sanitization required

---

## Testing Strategy

### Unit Tests
- [ ] Service tests
- [ ] Strategy tests (if applicable)
- [ ] Policy tests
- [ ] Scope tests

### Feature Tests
- [ ] Controller endpoint tests
- [ ] Authentication tests
- [ ] Authorization tests
- [ ] Validation tests

### Test Data
```php
// Realistic test data example
$project = Project::factory()->create([
    'name' => 'E-commerce Platform',
    'slug' => 'ecommerce-platform',
]);

$user = EndUser::factory()->create([
    'email' => 'developer@example.com',
    'project_id' => $project->id,
]);
```

---

## Implementation Checklist

### Phase 1: Setup
- [ ] Create migration
- [ ] Create model with scopes
- [ ] Create FormRequest
- [ ] Create Policy

### Phase 2: Business Logic
- [ ] Create Service
- [ ] Create Strategies (if needed)
- [ ] Create Resource

### Phase 3: HTTP Layer
- [ ] Create Controller
- [ ] Register routes
- [ ] Add middleware

### Phase 4: Testing
- [ ] Write unit tests
- [ ] Write feature tests
- [ ] Manual testing

### Phase 5: Documentation
- [ ] Update API docs
- [ ] Update CLAUDE.md if needed

---

## Questions / Open Issues

1. Question or issue description
2. Another question

---

## Changelog

| Date | Author | Changes |
|------|--------|---------|
| YYYY-MM-DD | Name | Initial draft |
