# Implementation Plan: Mini Helpdesk Portal

## Overview

Server-rendered Laravel 13 helpdesk application built on the Livewire starter kit (Livewire 4, Tailwind CSS v4, Flux UI free tier, Fortify auth). The plan is divided into eight phases: foundation data layer first, then auth/routing, then CRUD modules, then the ticket core, then dashboards, then reporting, and finally tests and docs. Each later phase depends on all earlier phases being complete.

The implementation language is **PHP 8.5 / Laravel 13** with **Pest v4** for testing.

---

## Tasks

- [x] 0. Phase 0 — Foundation: Enums, Models, Migrations, Factories, Seeders, TicketObserver

  - [x] 0.1 Create the four backed PHP enums in `app/Enums/`
    - Create `Role` (`Admin`, `Engineer`, `Client`), `TicketStatus` (`Open`, `InProgress`, `Resolved`, `Closed`), `Priority` (`Low`, `Medium`, `High`), `ClientStatus` (`Active`, `Inactive`) as PHP 8.1+ backed string enums using `php artisan make:enum` (or `make:class`).
    - All enum keys must be TitleCase; string values must be lowercase/snake_case as shown in the design.
    - _Requirements: 4.9_

  - [x] 0.2 Write migrations for all five tables
    - Using `php artisan make:migration`, create migrations in order: `clients`, `users` (modify existing if present), `tickets`, `ticket_comments`, `ticket_status_histories`, `monthly_report_remarks`.
    - Apply all columns, nullable FKs, indexes, and the composite index on `tickets(client_id, status)` exactly as specified.
    - Ensure migrations are idempotent and run cleanly with `php artisan migrate:fresh`.
    - _Requirements: 4.1, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7_

  - [x] 0.3 Create or update Eloquent models with casts, relationships, and `scopeVisibleTo`
    - Create/update `Client`, `User`, `Ticket`, `TicketComment`, `TicketStatusHistory`, `MonthlyReportRemark` models.
    - Cast enum columns via the `casts()` method; define all `belongsTo` / `hasMany` relationships per the ERD.
    - Implement `scopeVisibleTo(Builder $query, User $user): Builder` on `Ticket` using a `match` on `$user->role`.
    - Add the `#[Hidden]` attribute to `User::$password` to exclude it from serialisation.
    - _Requirements: 4.1, 4.2, 4.3, 4.5, 4.6, 4.7, 4.8, 4.9, 4.10_

  - [x] 0.4 Create model factories with enum-aware states
    - Run `php artisan make:factory` for `Client`, `Ticket`, `TicketComment`, `TicketStatusHistory`, `MonthlyReportRemark` (User factory likely already exists from starter kit — update it).
    - Add named states for each enum value where useful (e.g., `->open()`, `->high()`, `->admin()`).
    - _Requirements: 9.1–9.6, 13.1_

  - [x] 0.5 Implement `TicketNumberService` and register it in the container
    - Create `app/Services/TicketNumberService.php` with a `generate(): string` method using `DB::transaction` + `lockForUpdate` to produce sequential `TKT-NNNNN` numbers atomically.
    - Bind the class in `AppServiceProvider` (or a dedicated provider) so it can be resolved via the container.
    - _Requirements: 5.2, 5.3, 12.4_

  - [x] 0.6 Implement `TicketObserver` and register it via `TicketObserverServiceProvider`
    - Create `app/Observers/TicketObserver.php` handling `creating` (calls `TicketNumberService::generate()`, aborts on failure) and `updating` (writes `TicketStatusHistory` row when `status` changed; sets/clears `resolved_at` on Resolved transitions).
    - Create `app/Providers/TicketObserverServiceProvider.php` that registers the observer; add it to `bootstrap/providers.php`.
    - _Requirements: 5.1, 5.3, 5.4, 5.5, 5.6, 5.7, 12.5_

  - [ ]* 0.7 Write unit tests for `TicketNumberService` and `TicketObserver`
    - `tests/Unit/TicketNumberServiceTest.php`: format correctness, sequential increment, uniqueness across N creations (≥ 100 iterations using `it()->with()` or dataset).
    - `tests/Unit/TicketObserverTest` (or feature test): ticket number assigned on `creating`; status history row written on `updating`; `resolved_at` set on Resolved transition, cleared on transition away.
    - **Property 7: Every ticket creation produces a unique, correctly formatted ticket number**
    - **Property 8: Every status change produces a `TicketStatusHistory` row with correct values**
    - **Property 9: `resolved_at` is set on Resolved and cleared on transition away**
    - **Validates: Requirements 5.1–5.6**

  - [x] 0.8 Create seeders for all roles and demo data
    - Create `DatabaseSeeder` (or individual seeders) that produce: 1 Admin, 2 Engineers, 2 Clients (companies), 2 Client_Users, ≥ 10 tickets covering all statuses and priorities, at least one public and one internal comment on ≥ 3 tickets.
    - Run `php artisan migrate:fresh --seed` and verify it completes without errors.
    - _Requirements: 1.6, 9.1–9.6_

- [ ] 1. Phase 1 — Auth & Role Routing

  - [ ] 1.1 Disable Fortify registration and configure role-based redirects
    - In `config/fortify.php`, remove `Features::registration()` from the features array.
    - Implement a custom `AuthenticatedSessionController` or Fortify `LoginResponse` that redirects `/dashboard` to the correct role-specific URL (`/admin/dashboard`, `/engineer/dashboard`, `/client/dashboard`).
    - Ensure `/register` returns a 302 redirect to `/login`.
    - _Requirements: 1.3, 1.4, 2.6, 2.7, 2.8_

  - [ ] 1.2 Create `role` middleware and define all route groups
    - Create `app/Http/Middleware/RoleMiddleware.php` that reads `auth()->user()->role` and aborts with 403 if the required role is not matched; register it as `role` in `bootstrap/app.php`.
    - Define all route groups in `routes/web.php`: Admin-only group (`/clients`, `/users`), shared ticket group, dashboard group, and report group — all behind `auth` middleware.
    - _Requirements: 3.4, 3.7, 6.8, 7.10_

  - [ ] 1.3 Register all Policies and Gates in `AuthServiceProvider`
    - Create `ClientPolicy`, `UserPolicy`, `TicketPolicy`, `TicketCommentPolicy` using `php artisan make:policy`.
    - Implement `viewAny`, `view`, `create`, `update` on each; `TicketPolicy::view()` must mirror `scopeVisibleTo` logic.
    - Implement `TicketCommentPolicy::create()` checking role and `is_internal` flag.
    - Register all policies in `AuthServiceProvider::$policies`.
    - _Requirements: 3.1, 3.2, 3.3, 3.5, 3.6, 3.8_

  - [ ] 1.4 Implement inactive-user login denial
    - Override Fortify's `AttemptToAuthenticate` pipeline action (or use a custom `LoginResponse` / event listener) to reject users where `is_active = false`, returning the same generic error message used for invalid credentials.
    - _Requirements: 2.9_

  - [ ]* 1.5 Write feature tests for authentication flows
    - `tests/Feature/Auth/LoginTest.php`: valid credentials create session; invalid credentials rejected; inactive user rejected with same generic message; role-based redirect after login; already-authenticated `/login` redirects to dashboard.
    - **Property 1: Valid login always creates an authenticated session**
    - **Property 2: Invalid or inactive credentials always produce a generic error without a session**
    - **Validates: Requirements 2.2, 2.3, 2.9, 2.10, 1.3**

  - [ ]* 1.6 Write feature tests for RBAC middleware and policies
    - `tests/Feature/Clients/NonAdminClientTest.php` and `tests/Feature/Users/NonAdminUserTest.php`: Engineer and Client_User both get 403 on every `/clients` and `/users` route.
    - **Property 5: Non-Admin users are always denied Admin-only routes**
    - **Validates: Requirements 3.4, 6.8, 7.10**

- [ ] 2. Phase 2 — Client Management (Admin Only)

  - [ ] 2.1 Create `StoreClientRequest` and `UpdateClientRequest`
    - `php artisan make:request StoreClientRequest` — validate `name`, `contact_person`, `email` (email format, unique), `phone`, `status` (must be a valid `ClientStatus` value); `authorize()` returns `true` only for Admin via `$this->user()->role === Role::Admin`.
    - `php artisan make:request UpdateClientRequest` — same rules with `unique:clients,email,{$this->client->id}` for the self-exclusion.
    - _Requirements: 6.3, 6.5, 12.6_

  - [ ] 2.2 Implement `Clients\ClientTable` Livewire component
    - Paginated list of all clients showing name, contact person, email, phone, status; include a search input and status filter; add action buttons linking to create/edit.
    - Gate the component with `$this->authorize('viewAny', Client::class)`.
    - _Requirements: 6.1, 6.8_

  - [ ] 2.3 Implement `Clients\ClientForm` Livewire component
    - Handles both create and edit; resolves an optional `Client` model via `mount()`; uses `StoreClientRequest` / `UpdateClientRequest` validation by calling `$this->validate()` with the same rules.
    - On successful save, redirect back to `/clients` with a success flash.
    - Status toggle (Active/Inactive) updates `ClientStatus` without deleting the record.
    - _Requirements: 6.2, 6.3, 6.4, 6.5, 6.6, 6.7_

  - [ ]* 2.4 Write feature tests for Client Management
    - `tests/Feature/Clients/AdminClientTest.php`: list shows all clients; create with valid data persists; create with missing/invalid data returns field errors; edit updates record; status toggle persists without deletion.
    - **Property 10: Valid client payloads always persist; invalid payloads always return field-level errors without persisting**
    - **Validates: Requirements 6.1–6.8**

- [ ] 3. Phase 3 — User Management (Admin Only)

  - [ ] 3.1 Create `StoreUserRequest` and `UpdateUserRequest`
    - `StoreUserRequest`: validate `name`, `email` (unique), `password` (required, min 8), `role` (valid `Role` value), `client_id` (required when role is `Role::Client`, must exist in `clients` table); `authorize()` Admin only.
    - `UpdateUserRequest`: same rules; password nullable (leave unchanged if absent); `unique:users,email,{$this->user->id}` self-exclusion.
    - _Requirements: 7.2, 7.3, 7.5, 7.6, 7.7, 12.6_

  - [ ] 3.2 Implement `Users\UserTable` Livewire component
    - Paginated list showing name, email, role, active status; filter by role and active status; link to create/edit; gate with `$this->authorize('viewAny', User::class)`.
    - _Requirements: 7.1, 7.10_

  - [ ] 3.3 Implement `Users\UserForm` Livewire component
    - Create and edit user; conditionally show `client_id` dropdown when role is `Role::Client`; hash password with bcrypt on save; never render stored password in the view (empty password field on edit).
    - Active/inactive toggle sets `is_active` without deleting the record.
    - _Requirements: 7.2, 7.3, 7.4, 7.5, 7.6, 7.7, 7.8, 7.9, 13.7_

  - [ ]* 3.4 Write feature tests for User Management
    - `tests/Feature/Users/AdminUserTest.php`: list shows all users; create hashes password; client_id required for Client role; duplicate email rejected; edit without password leaves existing hash unchanged; active toggle works.
    - **Property 11: Valid user payloads persist with hashed password; duplicate emails return validation error**
    - **Validates: Requirements 7.1–7.10, 13.7**

- [x] 4. Checkpoint — Phase 0–3 complete
  - Run `php artisan test --compact` and confirm all tests pass; run `php artisan migrate:fresh --seed` and confirm no errors; verify `/clients` and `/users` are inaccessible to non-Admin roles.

- [ ] 5. Phase 4 — Ticket Module

  - [ ] 5.1 Create `TicketQueryService`
    - `app/Services/TicketQueryService.php` with `scopedQuery(User $user): Builder` (delegates to `Ticket::visibleTo($user)`) and `filteredQuery(User $user, array $filters): Builder` (applies `status`, `priority`, `client_id` filters on top of the scoped base query).
    - _Requirements: 8.5, 8.9, 12.1_

  - [ ] 5.2 Create all Ticket Form Requests
    - `StoreTicketRequest`: validate `title`, `description`, `priority` (valid `Priority`), `client_id` (required for Admin); `authorize()` returns false for Engineer (403).
    - `UpdateTicketRequest`: validate `status` (valid `TicketStatus`) for all permitted roles; additionally validate `assigned_engineer_id` and `priority` for Admin only; return 403 for Engineer attempting to change those fields; return 403 for Client_User attempting to change `status` or `priority`.
    - `StoreCommentRequest` (or inline Livewire validation): `comment` required; `is_internal` boolean; `authorize()` rejects Client_User when `is_internal = true`.
    - _Requirements: 8.3, 8.4, 8.10, 8.11, 8.12, 8.13, 8.16, 12.6_

  - [ ] 5.3 Implement `Tickets\TicketTable` Livewire component
    - Role-scoped paginated ticket list using `TicketQueryService::filteredQuery()`; show ticket number, title, client name, priority, status, assigned engineer, updated_at; filter dropdowns for status and priority; Admin additionally gets client filter.
    - _Requirements: 8.5, 8.6, 8.7, 8.8, 8.9_

  - [ ] 5.4 Implement `Tickets\TicketForm` Livewire component
    - Create and edit; fields rendered conditionally by role (client_id selector only for Admin, assigned_engineer_id only for Admin); uses `StoreTicketRequest` / `UpdateTicketRequest`; status change routed through Eloquent save so `TicketObserver` fires.
    - _Requirements: 8.1, 8.2, 8.3, 8.4, 8.10, 8.11, 8.12, 8.13, 8.19_

  - [ ] 5.5 Implement `Tickets\TicketDetail` Livewire component
    - Full ticket view: all metadata, status history timeline (`ticket_status_histories` ordered by `changed_at` ASC), action panel (edit button for permitted roles).
    - Gate with `$this->authorize('view', $ticket)`.
    - _Requirements: 8.18, 3.5, 3.6_

  - [ ] 5.6 Implement `Tickets\CommentThread` Livewire component
    - Display comments; show `is_internal` badge only to Admin/Engineer; hide `is_internal` comments entirely from Client_User; provide submit form with `is_internal` toggle for Admin/Engineer only.
    - _Requirements: 8.14, 8.15, 8.16, 8.17_

  - [ ]* 5.7 Write property-based test for `scopeVisibleTo` consistency
    - `tests/Feature/Tickets/ScopeVisibleToTest.php`: for a dataset of 100+ tickets across roles, assert Admin sees all, Engineer sees only assigned, Client_User sees only own-client.
    - **Property 3: `scopeVisibleTo` returns exactly the permitted ticket set for every role**
    - **Property 4: `TicketPolicy::view()` and `scopeVisibleTo` are always consistent**
    - **Validates: Requirements 3.2, 3.3, 8.5–8.8**

  - [ ]* 5.8 Write feature tests for Ticket access control
    - `tests/Feature/Tickets/EngineerTicketTest.php`: only assigned tickets visible; PUT with `assigned_engineer_id` or `priority` returns 403; status-only update succeeds.
    - `tests/Feature/Tickets/ClientTicketTest.php`: own-client tickets only; PUT with `status` or `priority` returns 403; `is_internal=true` comment POST returns 403.
    - **Property 6: Role-based ticket access control is consistently enforced at the HTTP layer**
    - **Property 12: Engineer role-based field restrictions enforced regardless of UI state**
    - **Property 13: Client_User cannot change ticket status or priority via any HTTP request**
    - **Property 15: Internal comments never visible to Client_User**
    - **Property 16: Client_User `is_internal=true` comment submissions always return 403**
    - **Validates: Requirements 3.5, 3.6, 8.11, 8.12, 8.13, 8.16, 8.17, 13.2, 13.3**

  - [ ]* 5.9 Write feature tests for ticket CRUD (Admin)
    - `tests/Feature/Tickets/AdminTicketTest.php`: create with valid data; create with invalid data returns field errors; edit all fields; comment with `is_internal` true/false; filter by status, priority, client.
    - **Property 14: Ticket list filters always return a subset where every ticket matches all filter conditions**
    - **Validates: Requirements 8.1, 8.3, 8.6, 8.9, 8.10, 8.14**

- [x] 6. Checkpoint — Phase 4 complete
  - Run `php artisan test --compact` and confirm all tests pass; verify `TicketObserver` fires correctly by creating and updating a ticket via the UI and checking `ticket_status_histories` in the database.

- [ ] 7. Phase 5 — Dashboards

  - [ ] 7.1 Implement `DashboardMetricsService`
    - `app/Services/DashboardMetricsService.php` with `metricsFor(User $user): array` (status-grouped counts; unassigned count for Admin; all sourced from `TicketQueryService`) and `latestTickets(User $user, int $limit = 5): Collection` (5 most recently updated visible tickets).
    - _Requirements: 10.1, 10.2, 10.3, 10.4, 10.6, 12.2_

  - [ ] 7.2 Implement `Dashboard\AdminDashboard` Livewire component
    - Metric cards (total by each `TicketStatus`, unassigned count); Chart.js status breakdown doughnut (pass data via `@js()` or `wire:init`); latest-5 tickets table.
    - Compute all metrics from `DashboardMetricsService` on each `render()` call — no hardcoded values.
    - _Requirements: 10.1, 10.4, 10.5, 10.6_

  - [ ] 7.3 Implement `Dashboard\EngineerDashboard` Livewire component
    - Engineer-scoped metric cards (tickets by status for this engineer); Chart.js status chart scoped to engineer; latest-5 assigned tickets.
    - _Requirements: 10.2, 10.4, 10.5, 10.6_

  - [ ] 7.4 Implement `Dashboard\ClientDashboard` Livewire component
    - Client-scoped metric cards (tickets by status for this client); Chart.js status chart scoped to client; latest-5 client tickets.
    - _Requirements: 10.3, 10.4, 10.5, 10.6_

  - [ ] 7.5 Wire `/dashboard` redirect to role-specific dashboard route
    - `GET /dashboard` controller or route closure reads `auth()->user()->role` and issues a `redirect()` to the appropriate dashboard URL.
    - _Requirements: 2.6, 2.7, 2.8_

  - [ ]* 7.6 Write feature tests for dashboards
    - `tests/Feature/Dashboard/DashboardTest.php`: Admin, Engineer, Client_User each load their dashboard; metric counts match manual DB aggregates for a controlled fixture; latest-5 ordered by `updated_at` DESC; counts update after a ticket status change on next load.
    - **Property 17: Dashboard metrics always equal live DB aggregate queries at time of load**
    - **Validates: Requirements 10.1–10.6**

- [ ] 8. Phase 6 — Monthly Report

  - [ ] 8.1 Implement `MonthlyReportService`
    - `app/Services/MonthlyReportService.php` with `generateReport(User $user, int $clientId, int $month, int $year): array` (total count, by-status counts, by-priority counts, ticket list — all sourced via `TicketQueryService`) and `saveRemark(int $clientId, int $month, int $year, string $text, User $author): MonthlyReportRemark` (upsert via `updateOrCreate`).
    - _Requirements: 11.1, 11.3, 11.4, 11.5, 11.9, 12.3_

  - [ ] 8.2 Create `UpdateMonthlyReportRequest` for month/year validation
    - Validate `month` (integer, 1–12), `year` (integer, 2000–2100), `client_id` (required for Admin, must exist), `remarks` (nullable, max 1000 characters).
    - _Requirements: 11.6_

  - [ ] 8.3 Implement `Reports\MonthlyReport` Livewire component
    - Admin: client selector dropdown, month/year pickers, report body sourced from `MonthlyReportService`, remarks textarea with save button (success/error flash).
    - Client_User: no client selector (auto-scoped to own client); month/year pickers only.
    - Empty period shows zeroed report, not an error.
    - _Requirements: 11.1, 11.2, 11.3, 11.4, 11.5, 11.6, 11.8_

  - [ ] 8.4 Create the print view at `/reports/monthly/print`
    - Blade view using a minimal layout that contains no navigation, sidebar, or application chrome — report content only.
    - Route bound to the same query string parameters (client, month, year); gate to `auth` middleware.
    - _Requirements: 11.7_

  - [ ]* 8.5 Write feature tests for Monthly Report
    - `tests/Feature/Reports/MonthlyReportTest.php`: accurate totals match DB; Admin can select any client; Client_User scoped to own client; upsert semantics (second save updates, not inserts); zeroed counts for empty period; month outside 1–12 rejected; print view contains no `<nav>` element.
    - **Property 18: Monthly report totals always match actual ticket counts in the database**
    - **Property 19: Monthly report remark upsert always results in exactly one record per (client, month, year)**
    - **Validates: Requirements 11.1–11.9**

- [x] 9. Checkpoint — Phase 5–6 complete
  - Run `php artisan test --compact`; verify print view renders without navigation; check that remark upsert creates only one `monthly_report_remarks` row for repeated saves.

- [ ] 10. Phase 7 — Testing, Hardening, README

  - [ ] 10.1 Audit codebase for hard deletes and raw password exposure
    - Search all PHP files for `->delete()` calls targeting `tickets` or `clients`; remove any found. Search Blade views for any `{{ $user->password }}` or logged password values; remove.
    - _Requirements: 4.10, 13.7, 13.8_

  - [ ] 10.2 Run Larastan static analysis and fix all reported issues
    - Run `vendor/bin/phpstan analyse --memory-limit=512M`; resolve all errors at the configured level before proceeding.
    - _Requirements: 13.5_

  - [ ] 10.3 Write remaining Pest feature tests to achieve full role × module coverage
    - Ensure the following test files exist and pass: `Auth/LoginTest`, `Clients/AdminClientTest`, `Clients/NonAdminClientTest`, `Users/AdminUserTest`, `Users/NonAdminUserTest`, `Tickets/AdminTicketTest`, `Tickets/EngineerTicketTest`, `Tickets/ClientTicketTest`, `Tickets/ScopeVisibleToTest`, `Tickets/TicketObserverTest`, `Dashboard/DashboardTest`, `Reports/MonthlyReportTest`.
    - Each file covers: list, view, create, edit, comment (where applicable) for permitted roles; 403 assertions for denied roles.
    - _Requirements: 13.1, 13.2, 13.3, 13.4_

  - [ ]* 10.4 Write unit tests for `TicketQueryService`, `DashboardMetricsService`, `MonthlyReportService`
    - `tests/Unit/TicketQueryServiceTest.php`: filter combinations produce expected results.
    - `tests/Unit/DashboardMetricsServiceTest.php`: aggregate counts match manually computed values.
    - `tests/Unit/MonthlyReportServiceTest.php`: report totals correct; upsert semantics.
    - **Validates: Requirements 12.1, 12.2, 12.3**

  - [ ] 10.5 Final test run and green suite confirmation
    - Run `php artisan test --compact`; all tests must pass with zero failures.
    - _Requirements: 13.5_

  - [ ] 10.6 Write `README.md`
    - Sections: architecture overview, ERD (copy Mermaid diagram from design), folder structure, Docker setup instructions (`docker compose up`), seed credentials table, role summary, known limitations, possible future improvements.
    - _Requirements: 13.6_

---

## Notes

- Tasks marked with `*` are optional and can be skipped for a faster MVP, but all property tests MUST run if included.
- Each phase depends on the completion of all previous phases. Do not start Phase N+1 until Phase N tasks are green.
- `php artisan migrate:fresh --seed` must complete without errors at any checkpoint.
- All form validation lives in Form Request classes — never inline in Livewire components.
- No `delete()` calls or delete routes target `tickets` or `clients` anywhere in the codebase.
- Passwords are never logged, rendered, or returned in any HTTP response; edit forms always render an empty password field.
- All dashboard metric values are computed from live aggregate DB queries — no hardcoded counts in views or service classes.
- Run `vendor/bin/pint --dirty --format agent` after modifying any PHP files to enforce code style.


## Task Dependency Graph

```json
{
  "waves": [
    { "id": 0, "tasks": ["0.1"] },
    { "id": 1, "tasks": ["0.2"] },
    { "id": 2, "tasks": ["0.3", "0.5"] },
    { "id": 3, "tasks": ["0.4", "0.6"] },
    { "id": 4, "tasks": ["0.8", "0.7"] },
    { "id": 5, "tasks": ["1.1", "1.2", "1.3"] },
    { "id": 6, "tasks": ["1.4"] },
    { "id": 7, "tasks": ["1.5", "1.6", "2.1", "3.1"] },
    { "id": 8, "tasks": ["2.2", "3.2"] },
    { "id": 9, "tasks": ["2.3", "3.3"] },
    { "id": 10, "tasks": ["2.4", "3.4"] },
    { "id": 11, "tasks": ["5.1", "5.2"] },
    { "id": 12, "tasks": ["5.3", "5.4"] },
    { "id": 13, "tasks": ["5.5", "5.6"] },
    { "id": 14, "tasks": ["5.7", "5.8", "5.9"] },
    { "id": 15, "tasks": ["7.1"] },
    { "id": 16, "tasks": ["7.2", "7.3", "7.4", "7.5"] },
    { "id": 17, "tasks": ["7.6", "8.1", "8.2"] },
    { "id": 18, "tasks": ["8.3", "8.4"] },
    { "id": 19, "tasks": ["8.5"] },
    { "id": 20, "tasks": ["10.1", "10.2", "10.3", "10.4"] },
    { "id": 21, "tasks": ["10.5"] },
    { "id": 22, "tasks": ["10.6"] }
  ]
}
```
