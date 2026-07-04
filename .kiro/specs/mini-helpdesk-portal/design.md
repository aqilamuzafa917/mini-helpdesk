# Design Document — Mini Helpdesk Portal

## Overview

The Mini Helpdesk Portal is a server-rendered fullstack Laravel 13 application that centralises IT support ticket management for a small team. Three roles — Admin, Engineer, and Client — operate scoped views of the same underlying dataset. The application is built on top of the official `laravel/livewire-starter-kit`, which ships Livewire 4, Tailwind CSS v4, Flux UI (free tier), and Fortify-based authentication out of the box. There is no API layer, no background queues, and no WebSockets — all interaction happens through synchronous, server-rendered Blade/Livewire responses.

The system is currently scaffolded: the starter kit is installed, Chart.js is wired into `resources/js/app.js`, Docker Compose is present, Tailwind v4 and Flux UI are configured in `resources/css/app.css`, and the base layouts (`layouts/app.blade.php`, `layouts/auth.blade.php`) are in place. The remaining work is additive.

---

## Architecture

### Technology Stack

| Layer | Choice | Notes |
|---|---|---|
| Language | PHP 8.5 | Laravel 13 minimum; constructor property promotion, backed enums |
| Framework | Laravel 13 | `laravel/framework` ^13.17 |
| Frontend | Blade + Livewire 4 + Alpine.js | No SPA/API layer; reactive UI managed server-side |
| UI Components | Flux UI v2 (free tier) | Ships with starter kit; buttons, modals, forms, dropdowns |
| CSS | Tailwind CSS v4 | Configured via `@import 'tailwindcss'` in `app.css` |
| Auth | Laravel Fortify v1 | Login, logout, password reset; registration disabled |
| Charts | Chart.js (already imported globally via `window.Chart`) | Status breakdown doughnut/bar charts on dashboards |
| Database | MySQL 8 (Docker) / SQLite (local dev/tests) | Standard Laravel default |
| Testing | Pest v4 | Feature tests per role per module |
| Containerisation | Docker Compose (php, nginx, mysql, mailpit) | `docker compose up` reproducibility |

### Architectural Decisions

**No repository layer.** Eloquent + Form Requests + Service classes provides sufficient abstraction at this scope. A repository layer would add indirection with no benefit.

**`TicketQueryService` as the single scoping authority.** The "which tickets can this user see?" question is answered in exactly one place — `TicketQueryService` — and consumed by all services and Livewire components that need ticket data. This prevents scoping logic drifting across dashboards, reports, and the ticket list.

**`TicketObserver` in its own provider.** All ticket creation/update side effects (ticket number assignment, status history logging, `resolved_at` management) live in `TicketObserver`, registered via `TicketObserverServiceProvider`. The `Ticket` model stays a thin data class.

**Form Requests for all validation and authorisation.** Livewire components delegate validation to Form Request classes (`StoreTicketRequest`, `UpdateTicketRequest`, etc.) so validation logic is reusable, independently testable, and not embedded in component `mount()`/`save()` methods.

**No hard deletes for tickets or clients.** Status/active-flag changes only. There are no delete routes and no `delete()` calls targeting these two tables anywhere in the codebase.

---

## Components and Interfaces

### Route Structure

```
GET  /login                               Fortify (Livewire auth view)
POST /login                               Fortify
POST /logout                              Fortify

GET  /dashboard                           Role-dispatch: redirects to role-specific dashboard

# Admin-only routes (middleware: auth, role:admin)
GET        /clients                       Livewire: Clients\ClientTable
GET|POST   /clients/create                Livewire: Clients\ClientForm
GET|PUT    /clients/{client}/edit         Livewire: Clients\ClientForm
PUT        /clients/{client}/status       Livewire action (status toggle)

GET        /users                         Livewire: Users\UserTable
GET|POST   /users/create                  Livewire: Users\UserForm
GET|PUT    /users/{user}/edit             Livewire: Users\UserForm
PUT        /users/{user}/status           Livewire action (status toggle)

# Shared ticket routes (middleware: auth, scoped by policy + scopeVisibleTo)
GET        /tickets                       Livewire: Tickets\TicketTable
GET|POST   /tickets/create                Livewire: Tickets\TicketForm
GET        /tickets/{ticket}              Livewire: Tickets\TicketDetail
PUT        /tickets/{ticket}              Livewire: Tickets\TicketForm
POST       /tickets/{ticket}/comments     Livewire: Tickets\CommentThread

# Dashboard routes (middleware: auth)
GET        /admin/dashboard               Livewire: Dashboard\AdminDashboard
GET        /engineer/dashboard            Livewire: Dashboard\EngineerDashboard
GET        /client/dashboard              Livewire: Dashboard\ClientDashboard

# Report routes (middleware: auth)
GET        /reports/monthly               Livewire: Reports\MonthlyReport
GET        /reports/monthly/print         Blade view (print-only layout, no nav)
```

### Livewire Components

| Component | Responsibility |
|---|---|
| `Dashboard\AdminDashboard` | Admin metrics cards, Chart.js status chart, latest-5 tickets table |
| `Dashboard\EngineerDashboard` | Engineer-scoped metrics, chart, latest-5 tickets |
| `Dashboard\ClientDashboard` | Client-scoped metrics, chart, latest-5 tickets |
| `Clients\ClientTable` | Paginated client list with search and status filter |
| `Clients\ClientForm` | Create/edit client using `StoreClientRequest` / `UpdateClientRequest` |
| `Users\UserTable` | Paginated user list with role and status filters |
| `Users\UserForm` | Create/edit user; conditional `client_id` field when role = Client |
| `Tickets\TicketTable` | Role-scoped ticket list; filter by status, priority (and client for Admin) |
| `Tickets\TicketForm` | Create/edit ticket; fields rendered conditionally by role |
| `Tickets\TicketDetail` | Full ticket view; status history timeline; role-conditional action panel |
| `Tickets\CommentThread` | Comment list with internal/public toggle (admin/engineer); submit form |
| `Reports\MonthlyReport` | Client selector (Admin only), month/year pickers, report body, remarks field |

### Service Classes

| Service | Interface |
|---|---|
| `TicketQueryService` | `scopedQuery(User $user): Builder` — returns a base Eloquent query scoped to `$user`'s role via `scopeVisibleTo`; `filteredQuery(User $user, array $filters): Builder` — applies status, priority, client_id filters on top |
| `DashboardMetricsService` | `metricsFor(User $user): array` — returns status-grouped counts and unassigned count; `latestTickets(User $user, int $limit = 5): Collection` — most recently updated tickets for this user |
| `MonthlyReportService` | `generateReport(User $user, int $clientId, int $month, int $year): array` — full report data array; `saveRemark(int $clientId, int $month, int $year, string $text, User $author): MonthlyReportRemark` — upsert remark |
| `TicketNumberService` | `generate(): string` — returns next `TKT-NNNNN` formatted number, using a DB-level lock to guarantee uniqueness |

### Observers and Providers

| Class | Responsibility |
|---|---|
| `TicketObserver` | `creating()`: calls `TicketNumberService::generate()` and sets `ticket_number`. `updating()`: when `status` has changed, writes `TicketStatusHistory` row; sets/clears `resolved_at` on Resolved transitions |
| `TicketObserverServiceProvider` | Registers `TicketObserver` on the `Ticket` model |

### Policies

| Policy | Methods |
|---|---|
| `TicketPolicy` | `viewAny`, `view`, `create`, `update`, `addComment` — delegates visibility to same logic as `scopeVisibleTo` |
| `ClientPolicy` | `viewAny`, `view`, `create`, `update` — Admin only |
| `UserPolicy` | `viewAny`, `view`, `create`, `update` — Admin only |
| `TicketCommentPolicy` | `create` — checks role and `is_internal` flag |

### Form Requests

| Request | Validates |
|---|---|
| `StoreTicketRequest` | title, description, priority, client_id (Admin-submitted); role-based `authorize()` |
| `UpdateTicketRequest` | status (all permitted roles); assigned_engineer_id, priority (Admin only) |
| `StoreClientRequest` | name, contact_person, email, phone, status |
| `UpdateClientRequest` | Same as Store with unique-except-self on email if present |
| `StoreUserRequest` | name, email (unique), password, role; client_id required when role = Client |
| `UpdateUserRequest` | name, email (unique-except-self); password nullable (unchanged if absent); role; client_id when role = Client |

---

## Data Models

### Entity Relationship Diagram

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email UK
        string password
        string role
        bigint client_id FK
        boolean is_active
        timestamps
    }
    clients {
        bigint id PK
        string name
        string contact_person
        string email
        string phone
        text address
        string status
        timestamps
    }
    tickets {
        bigint id PK
        string ticket_number UK
        bigint client_id FK
        bigint created_by FK
        bigint assigned_engineer_id FK
        string title
        text description
        string priority
        string status
        timestamp resolved_at
        timestamps
    }
    ticket_comments {
        bigint id PK
        bigint ticket_id FK
        bigint user_id FK
        text comment
        boolean is_internal
        timestamps
    }
    ticket_status_histories {
        bigint id PK
        bigint ticket_id FK
        string old_status
        string new_status
        text notes
        bigint changed_by FK
        timestamp changed_at
    }
    monthly_report_remarks {
        bigint id PK
        bigint client_id FK
        tinyint month
        smallint year
        text remarks
        bigint created_by FK
        timestamps
    }

    users ||--o{ tickets : "created_by"
    users ||--o{ tickets : "assigned_engineer_id"
    users }o--|| clients : "client_id (nullable)"
    clients ||--o{ tickets : "client_id"
    tickets ||--o{ ticket_comments : "ticket_id"
    tickets ||--o{ ticket_status_histories : "ticket_id"
    clients ||--o{ monthly_report_remarks : "client_id"
    users ||--o{ ticket_comments : "user_id"
    users ||--o{ ticket_status_histories : "changed_by"
    users ||--o{ monthly_report_remarks : "created_by"
```

### Enums

```php
enum Role: string
{
    case Admin    = 'admin';
    case Engineer = 'engineer';
    case Client   = 'client';
}

enum TicketStatus: string
{
    case Open       = 'open';
    case InProgress = 'in_progress';
    case Resolved   = 'resolved';
    case Closed     = 'closed';
}

enum Priority: string
{
    case Low    = 'low';
    case Medium = 'medium';
    case High   = 'high';
}

enum ClientStatus: string
{
    case Active   = 'active';
    case Inactive = 'inactive';
}
```

All enums live in `app/Enums/` and are cast on their respective model columns via the `casts()` method.

### Key Model Details

**`Ticket` — `scopeVisibleTo` query scope:**

```php
public function scopeVisibleTo(Builder $query, User $user): Builder
{
    return match ($user->role) {
        Role::Admin    => $query,
        Role::Engineer => $query->where('assigned_engineer_id', $user->id),
        Role::Client   => $query->where('client_id', $user->client_id),
    };
}
```

**`TicketPolicy::view()` — must mirror scope:**

```php
public function view(User $user, Ticket $ticket): bool
{
    return match ($user->role) {
        Role::Admin    => true,
        Role::Engineer => $ticket->assigned_engineer_id === $user->id,
        Role::Client   => $ticket->client_id === $user->client_id,
    };
}
```

**`TicketNumberService::generate()` — uniqueness strategy:**

Uses a pessimistic DB lock (`lockForUpdate`) on the tickets table to read the current max number, increment it, and return the formatted string atomically. This prevents race conditions during concurrent ticket creation.

```php
public function generate(): string
{
    $max = DB::transaction(function () {
        $last = Ticket::lockForUpdate()->max('ticket_number');
        return $last ? ((int) substr($last, 4)) + 1 : 1;
    });

    return sprintf('TKT-%05d', $max);
}
```

### Database Indexes

| Table | Index |
|---|---|
| `users` | `email` (unique), `client_id` |
| `tickets` | `client_id`, `assigned_engineer_id`, `priority`, `status`, `created_at`; composite `(client_id, status)` |
| `ticket_comments` | `ticket_id` |
| `ticket_status_histories` | `ticket_id` |
| `monthly_report_remarks` | unique `(client_id, month, year)` |

---

## Correctness Properties

*A property is a characteristic or behavior that should hold true across all valid executions of a system — essentially, a formal statement about what the system should do. Properties serve as the bridge between human-readable specifications and machine-verifiable correctness guarantees.*

### Redundancy Elimination

Before writing properties, redundancies across the prework analysis were eliminated:

- Properties for "scoped ticket list per role" (Req 8.5–8.8) are subsumed by the `scopeVisibleTo` consistency property (Req 3.2/3.3).
- "Engineer returns 403 for non-assigned ticket" (Req 3.5) and "Client_User returns 403 for non-client ticket" (Req 3.6) are combined into a single access-control property using the same `scopeVisibleTo` logic.
- Dashboard metric properties (Req 10.1–10.6) are combined into one since all three roles share the same structural guarantee: metrics always equal live DB aggregates.
- "TicketObserver writes history on status change" (Req 8.19) is subsumed by the history-tracking property (Req 5.4).
- Remark create (11.4) is subsumed by the upsert property (11.3).
- Empty-month report (11.5) is captured as an edge case within the report accuracy property (11.1).

---

### Property 1: Valid login always creates an authenticated session

*For any* valid user stored in the database (any role, any email/password combination), submitting those credentials to `POST /login` always returns a successful authentication response and creates an authenticated session. No valid, active user credential combination is incorrectly rejected.

**Validates: Requirements 2.2**

---

### Property 2: Invalid or inactive credentials always produce a generic error without a session

*For any* credential pair that does not match an active user (wrong email, wrong password, or user with `is_active = false`), submitting to `POST /login` always returns a validation error message (the same generic message regardless of which field is wrong) and never creates an authenticated session. No information about which field was incorrect is leaked.

**Validates: Requirements 2.3, 2.9**

---

### Property 3: `scopeVisibleTo` returns exactly the permitted ticket set for every role

*For any* user with a given role and *for any* dataset of tickets in the database:
- Admin: `Ticket::visibleTo($user)->get()` returns all tickets.
- Engineer: the result contains only tickets where `assigned_engineer_id = $user->id`.
- Client: the result contains only tickets where `client_id = $user->client_id`.

No ticket outside the permitted set is ever included, and no permitted ticket is ever excluded.

**Validates: Requirements 3.2, 8.5, 8.6, 8.7, 8.8**

---

### Property 4: `TicketPolicy::view()` and `scopeVisibleTo` are always consistent

*For any* user and *for any* ticket in the database, `TicketPolicy::view($user, $ticket)` returns `true` if and only if that ticket would appear in `Ticket::visibleTo($user)->where('id', $ticket->id)->exists()`. The two mechanisms never disagree about the same user/ticket pair.

**Validates: Requirements 3.3**

---

### Property 5: Non-Admin users are always denied Admin-only routes

*For any* user with `role = Engineer` or `role = Client`, and *for any* route in the Admin-only group (`/clients`, `/users`, and all sub-routes), the HTTP response is always 403 Forbidden regardless of the request payload.

**Validates: Requirements 3.4, 6.8, 7.10**

---

### Property 6: Role-based ticket access control is consistently enforced at the HTTP layer

*For any* Engineer and *for any* ticket where `assigned_engineer_id != engineer.id`, a GET/PUT request to that ticket's route always returns 403. *For any* Client_User and *for any* ticket where `client_id != user.client_id`, the same holds. Access is never granted through a direct HTTP request that bypasses UI-level restrictions.

**Validates: Requirements 3.5, 3.6**

---

### Property 7: Every ticket creation always produces a non-null, correctly formatted, unique ticket number

*For any* valid ticket creation payload submitted by an Admin or Client_User, the resulting persisted `Ticket` record always has:
- A non-null `ticket_number`.
- A value matching the format `TKT-NNNNN` (exactly 5 zero-padded digits).
- A value distinct from every other `ticket_number` in the `tickets` table.

**Validates: Requirements 5.1, 5.2, 8.1, 8.2**

---

### Property 8: Every status change always produces a `TicketStatusHistory` row with correct values

*For any* ticket and *for any* valid status transition (Admin or Engineer changing status via the edit form), a new row is always written to `ticket_status_histories` with:
- `old_status` equal to the ticket's status before the change.
- `new_status` equal to the new status value.
- `changed_by` equal to the authenticated user's id.
- `changed_at` set to a timestamp at or after the update time.

**Validates: Requirements 5.4, 8.19**

---

### Property 9: `resolved_at` is always set on transition to Resolved and always cleared on transition away

*For any* ticket transitioned to `TicketStatus::Resolved`, `resolved_at` is always a non-null timestamp after the transition. *For any* ticket currently in `Resolved` status transitioned to any other status, `resolved_at` is always `null` after the transition. This is a round-trip invariant.

**Validates: Requirements 5.5, 5.6**

---

### Property 10: Valid client payloads always result in a persisted record; invalid payloads always return field-level errors without persisting

*For any* payload where all required fields (name, contact_person, email, phone, status) are present and valid, submitting the client creation or edit form always persists a `Client` record with those exact values. *For any* payload where one or more required fields are absent or invalid, the response always contains field-level validation errors and no new/modified record is persisted.

**Validates: Requirements 6.2, 6.3, 6.4, 6.5**

---

### Property 11: Valid user payloads always result in a persisted record with a hashed password; duplicate emails always return a validation error

*For any* valid user creation payload, a `User` record is always persisted with the password stored as a bcrypt hash (never plaintext). *For any* email address already present in the `users` table, a creation attempt with that email always returns a validation error and no new record is persisted.

**Validates: Requirements 7.2, 7.5, 13.7**

---

### Property 12: Engineer role-based field restrictions are enforced at the HTTP layer regardless of UI state

*For any* Engineer user, *for any* ticket assigned to them, a direct `PUT` request including `assigned_engineer_id` or `priority` in the payload always returns 403. Only `status` changes are permitted, and only on tickets assigned to that Engineer.

**Validates: Requirements 8.11, 13.3**

---

### Property 13: Client_User cannot change ticket status or priority via any HTTP request

*For any* Client_User, *for any* ticket belonging to their client, a direct HTTP request containing `status` or `priority` fields always returns 403, regardless of the UI state at the time of submission.

**Validates: Requirements 8.12, 8.13, 13.2**

---

### Property 14: Ticket list filters always return a subset where every ticket matches all applied filter conditions

*For any* combination of active filters (status, priority, client_id for Admin), every ticket returned in the filtered list always satisfies every active filter condition simultaneously. No ticket outside the filter criteria is included.

**Validates: Requirements 8.9**

---

### Property 15: Internal comments are never visible to Client_User in any HTTP response

*For any* Client_User viewing *any* ticket detail page or comment endpoint, the response never includes comments where `is_internal = true`. The filtering holds regardless of how many internal comments are on the ticket.

**Validates: Requirements 8.17**

---

### Property 16: Client_User comment submissions with `is_internal = true` always return 403

*For any* Client_User and *for any* comment payload that sets `is_internal = true`, the HTTP response is always 403 Forbidden and no comment record is persisted.

**Validates: Requirements 8.16**

---

### Property 17: Dashboard metrics always equal live DB aggregate queries at the time of load

*For any* authenticated user and *for any* state of the tickets table, the counts displayed on the dashboard (total by status, unassigned count for Admin) always equal the result of executing the equivalent `GROUP BY status` aggregate queries against the database at the same moment — no hardcoded values, no stale cached data.

**Validates: Requirements 10.1, 10.2, 10.3, 10.6**

---

### Property 18: Monthly report totals always match actual ticket counts in the database for the selected period

*For any* client, month, and year combination that produces a non-empty ticket set, the totals in the monthly report (total count, by-status counts, by-priority counts) always equal the actual counts from a direct database query for `tickets WHERE client_id = ? AND MONTH(created_at) = ? AND YEAR(created_at) = ?`. For a period with no tickets, all counts are zero.

**Validates: Requirements 11.1, 11.5**

---

### Property 19: Monthly report remark upsert always results in exactly one record per (client, month, year)

*For any* (client_id, month, year) triple, saving a `MonthlyReportRemark` any number of times always results in exactly one record in the `monthly_report_remarks` table for that combination, containing the most recently saved text.

**Validates: Requirements 11.3, 11.4**

---

## Error Handling

### Validation Errors

All user-submitted data is validated through Form Request classes. Livewire components display field-level `$errors->get('field')` inline beneath each input using Flux UI's error display pattern. No flash messages for validation — errors are displayed in context.

### Authentication and Authorisation Errors

- `401 Unauthenticated`: Fortify's `AuthenticateSession` middleware redirects to `/login` for all protected routes.
- `403 Forbidden`: Laravel's `AuthorizationException` (thrown by `$this->authorize()` in Livewire components) returns a 403 response. The application renders a minimal 403 error view.
- Generic login errors (invalid credentials, inactive account) use the same message string to prevent account enumeration.

### Ticket Number Generation Failure

If `TicketNumberService::generate()` throws (e.g., DB lock timeout), the `TicketObserver::creating()` event returns `false`, aborting the `save()`. The calling Livewire component catches the exception and displays a generic "ticket could not be created" error to the user. No partial record is persisted.

### Report Validation

Month values outside 1–12 or years outside a reasonable range (e.g., 2000–2100) are rejected by `UpdateMonthlyReportRequest` validation. An empty result set (no tickets for the period) is a valid state that returns a zeroed report, not an error or 404.

### Password Handling

Passwords are never logged, never returned in HTTP responses (edit forms render an empty password field), and the `UpdateUserRequest` leaves the stored password unchanged when the password field is absent or empty. The `#[Hidden]` attribute on `User` ensures password is excluded from serialisation.

---

## Testing Strategy

### Dual Approach

Unit-level and feature-level tests together. Feature tests validate the HTTP and Livewire layer using real HTTP requests against a SQLite test database. Unit/integration tests cover service class logic in isolation.

### Property-Based Testing

This project uses **Pest v4** with **[pest-plugin-hypothetic](https://github.com/pestphp/pest-plugin-hypothetic)** or inline data generators as the property-based testing mechanism. Where a full property-based library is not available, properties are validated by parameterised dataset tests (`it()->with()`) covering a broad range of generated inputs. Each property test runs a minimum of **100 iterations**.

Each property test is tagged with a comment referencing the design property:

```php
// Feature: mini-helpdesk-portal, Property 3: scopeVisibleTo returns exactly the permitted ticket set
```

### Unit Tests (`tests/Unit/`)

Covering service class logic in isolation:

- `TicketNumberServiceTest` — format correctness, sequential increment, uniqueness across N creations
- `TicketQueryServiceTest` — filter combinations produce expected query clauses
- `DashboardMetricsServiceTest` — aggregate counts match manually computed values for a controlled fixture
- `MonthlyReportServiceTest` — report totals match expected values; upsert behaviour for remarks

### Feature Tests (`tests/Feature/`)

One test file per role per module, e.g.:

- `Auth/LoginTest` — valid credentials, invalid credentials, inactive user, role-based redirect, already-authenticated redirect
- `Clients/AdminClientTest` — CRUD permitted; field validation
- `Clients/NonAdminClientTest` — 403 for Engineer and Client_User on every client route
- `Users/AdminUserTest` — CRUD, password hashing, client_id requirement for Client role
- `Users/NonAdminUserTest` — 403 for non-Admin on every user route
- `Tickets/AdminTicketTest` — full create/edit/comment access; filters work correctly
- `Tickets/EngineerTicketTest` — only assigned tickets visible; 403 on priority/assignment changes; status-only updates permitted
- `Tickets/ClientTicketTest` — own-client tickets only; 403 on status/priority changes; is_internal comment rejected
- `Tickets/TicketObserverTest` — ticket number assigned on creation; status history row written on status change; resolved_at set/cleared
- `Dashboard/DashboardTest` — metrics match DB aggregates per role; latest-5 ordering correct
- `Reports/MonthlyReportTest` — accurate totals; scoped by role; upsert semantics; zeroed empty period; invalid month/year rejected; print view contains no nav

### Test Configuration

- Database: SQLite in-memory (`:memory:`) via `phpunit.xml` for speed
- `RefreshDatabase` or `LazilyRefreshDatabase` trait on all feature tests
- Factories for all models with realistic faker data and enum-aware states
- `php artisan test --compact` must pass green with zero failures

### What Is NOT Property-Tested

The following are tested with example-based tests only (the PBT decision guide ruled them out):

- **UI rendering** (Flux component output, chart markup) — snapshot or visual review
- **Infrastructure** (Docker, migrations, seed data) — smoke tests and CI checks
- **Architectural separation** (observer in its own provider, no `delete()` calls) — code review and static analysis via Larastan
