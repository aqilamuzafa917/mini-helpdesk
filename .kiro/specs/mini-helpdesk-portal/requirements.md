# Requirements Document

## Introduction

The Mini Helpdesk Portal is a fullstack Laravel 13 web application that enables a small IT support team to manage client support tickets. Three roles — Admin, Engineer, and Client — each see a scoped view of the system. Admins manage clients and users, assign tickets, and generate monthly reports. Engineers work their assigned ticket queue and collaborate via comments. Clients submit tickets and track their resolution status.

The application is server-rendered using Blade + Livewire 4 + Flux UI (free tier) with Tailwind CSS v4. Authentication is scaffolded with the official `laravel/livewire-starter-kit` (Fortify-based). There is no API layer, no queues, no notifications, and no permanent deletion of tickets or clients.

---

## Glossary

- **System**: The Mini Helpdesk Portal application as a whole.
- **Admin**: A user with `role = Role::Admin`. Has full access to all entities.
- **Engineer**: A user with `role = Role::Engineer`. Can only access tickets assigned to them.
- **Client_User**: A user with `role = Role::Client`. Belongs to a Client (company) and can only access that client's tickets.
- **Client**: A company or organisation registered in the `clients` table. Not to be confused with Client_User.
- **Ticket**: A support request raised against a Client, tracked through statuses Open → InProgress → Resolved → Closed.
- **TicketStatus**: A PHP backed enum with values `Open`, `InProgress`, `Resolved`, `Closed`.
- **Priority**: A PHP backed enum with values `Low`, `Medium`, `High`.
- **Role**: A PHP backed enum with values `Admin`, `Engineer`, `Client`.
- **ClientStatus**: A PHP backed enum with values `Active`, `Inactive`.
- **TicketObserver**: An Eloquent observer that fires on ticket creation and status change events.
- **TicketNumberService**: A service class responsible for generating unique, human-readable ticket numbers.
- **TicketQueryService**: A service class that centralises all ticket-scoping and filtering logic.
- **DashboardMetricsService**: A service class that produces per-role dashboard aggregate metrics, built on top of `TicketQueryService`.
- **MonthlyReportService**: A service class that produces monthly ticket summaries per client, built on top of `TicketQueryService`.
- **scopeVisibleTo**: An Eloquent query scope on the `Ticket` model that filters tickets to those visible to a given user based on role.
- **Form_Request**: A Laravel Form Request class responsible for validation and authorization of a given HTTP action (e.g., `StoreTicketRequest`).
- **Flux_UI**: The Flux UI component library (free tier, v2) shipped with the Livewire starter kit.
- **Fortify**: Laravel Fortify v1, providing authentication actions (login, logout, password reset). Registration is disabled.
- **Mailpit**: The local mail-catching service used in the Docker development environment.
- **MonthlyReportRemark**: A free-text remark written by an Admin for a specific `(client_id, month, year)` combination, stored in `monthly_report_remarks`.
- **internal_comment**: A `TicketComment` with `is_internal = true`, visible to Admin and Engineer only.
- **public_comment**: A `TicketComment` with `is_internal = false`, visible to all users with access to the ticket.

---

## Requirements

### Requirement 1: Project Bootstrap and Environment Setup

**User Story:** As a developer, I want a reproducible Docker-based development environment, so that I can onboard quickly and run the application without manual dependency installation.

#### Acceptance Criteria

1. THE System SHALL be bootstrapped using `laravel new` with the official Livewire starter kit, scaffolding Livewire 4, Tailwind CSS v4, Flux_UI free tier, and Fortify-based authentication.
2. WHEN `docker compose up` is executed, THE System SHALL start php, nginx, mysql, and mailpit containers and serve the application on port 80, returning an HTTP 200 response on `/login`.
3. WHEN a request is made to `/register`, THE System SHALL return an HTTP 302 redirect to `/login` without rendering a registration form.
4. THE System SHALL disable Fortify's `registration` feature flag in `config/fortify.php` so that the `/register` route returns an HTTP redirect (302) or not-found (404) response rather than a functional form.
5. WHERE Fortify's `registration` feature flag is re-enabled in configuration, THE System SHALL allow the registration route to function normally.
6. WHEN `php artisan migrate:fresh --seed` is executed, THE System SHALL complete without errors and populate the database with the seed data defined in Requirement 9.

---

### Requirement 2: Authentication

**User Story:** As any user, I want to log in with my email and password, so that I can access the portal.

#### Acceptance Criteria

1. THE System SHALL provide a login page at `/login` that accepts an email address (max 254 characters) and a password (max 72 characters).
2. WHEN a user submits valid credentials at `/login`, THE System SHALL authenticate the user and create an authenticated session.
3. WHEN a user submits invalid credentials at `/login`, THE System SHALL display a generic validation error message without revealing which field was incorrect, and SHALL NOT create an authenticated session.
4. WHEN an authenticated user submits a POST request to `/logout`, THE System SHALL invalidate the session and redirect the user to `/login`.
5. WHILE a user is unauthenticated, THE System SHALL redirect any request to a protected route to `/login`.
6. WHEN an Admin authenticates successfully, THE System SHALL redirect the Admin to the Admin dashboard.
7. WHEN an Engineer authenticates successfully, THE System SHALL redirect the Engineer to the Engineer dashboard.
8. WHEN a Client_User authenticates successfully, THE System SHALL redirect the Client_User to the Client dashboard.
9. IF a user's `is_active` flag is `false`, THEN THE System SHALL deny login and display a generic error message (the same message used for invalid credentials) to prevent account enumeration.
10. WHEN an already-authenticated user requests `/login`, THE System SHALL redirect them to their role-appropriate dashboard without showing the login form.

---

### Requirement 3: Role-Based Access Control

**User Story:** As a developer, I want all routes and actions to be protected by role-based policies, so that no user can access or modify data outside their permitted scope.

#### Acceptance Criteria

1. THE System SHALL enforce authorization on every route using Laravel Policies and Gates registered centrally in `AuthServiceProvider`.
2. THE System SHALL define a `scopeVisibleTo(Builder $query, User $user)` query scope on the `Ticket` model that returns all tickets for Admin, only tickets where `assigned_engineer_id` matches the Engineer's id for Engineer, and only tickets where `client_id` matches the Client_User's `client_id` for Client_User.
3. THE `TicketPolicy::view()` method SHALL apply the same visibility logic as `scopeVisibleTo` so that single-record and collection-level authorization cannot produce different results for the same user and ticket.
4. WHEN any non-Admin user requests a route in the Admin-only route group (`/clients`, `/users`), THE System SHALL return a 403 Forbidden response.
5. WHEN an Engineer requests a ticket not assigned to them, THE System SHALL return a 403 Forbidden response.
6. WHEN a Client_User requests a ticket not belonging to their Client, THE System SHALL return a 403 Forbidden response.
7. THE System SHALL define role-based middleware on route groups so that authorization is enforced at the routing layer before policies are evaluated.
8. WHEN an Engineer requests a route in the ticket module for a ticket assigned to them, THE System SHALL permit access to the ticket detail, the related client name, and public comments (where `is_internal = false`) on that ticket.

---

### Requirement 4: Database Schema and Models

**User Story:** As a developer, I want a well-defined database schema with proper indexes and Eloquent relationships, so that queries are efficient and the domain model is accurately represented.

#### Acceptance Criteria

1. THE System SHALL create a `users` table with columns: `id`, `name`, `email` (unique, indexed), `password`, `role` (string cast to `Role` enum), `client_id` (nullable FK to `clients.id`, indexed), `is_active` (boolean, default true), `timestamps`.
2. THE System SHALL create a `clients` table with columns: `id`, `name`, `contact_person`, `email`, `phone`, `address` (nullable text), `status` (string cast to `ClientStatus` enum), `timestamps`.
3. THE System SHALL create a `tickets` table with columns: `id`, `ticket_number` (string, unique), `client_id` (FK to `clients.id`, indexed), `created_by` (FK to `users.id`), `assigned_engineer_id` (nullable FK to `users.id`, indexed), `title`, `description` (text), `priority` (string cast to `Priority` enum, indexed), `status` (string cast to `TicketStatus` enum, indexed, default `open`), `resolved_at` (nullable timestamp), `created_at` (indexed), `updated_at`.
4. THE System SHALL create a composite index on `tickets(client_id, status)`.
5. THE System SHALL create a `ticket_comments` table with columns: `id`, `ticket_id` (FK to `tickets.id`, indexed), `user_id` (FK to `users.id`), `comment` (text), `is_internal` (boolean, default false), `timestamps`.
6. THE System SHALL create a `ticket_status_histories` table with columns: `id`, `ticket_id` (FK to `tickets.id`, indexed), `old_status` (string, nullable), `new_status` (string), `notes` (nullable text), `changed_by` (FK to `users.id`), `changed_at` (timestamp, defaults to current datetime).
7. THE System SHALL create a `monthly_report_remarks` table with columns: `id`, `client_id` (FK to `clients.id`), `month` (tinyint, values 1–12), `year` (smallint), `remarks` (nullable text), `created_by` (FK to `users.id`), `timestamps`, and a unique index on `(client_id, month, year)`.
8. THE System SHALL define Eloquent relationships: `User` belongsTo `Client` (nullable); `Client` hasMany `User`; `Client` hasMany `Ticket`; `Ticket` belongsTo `Client`, belongsTo creator `User`, belongsTo assigned engineer `User`; `Ticket` hasMany `TicketComment`; `Ticket` hasMany `TicketStatusHistory`; `TicketComment` belongsTo `Ticket` and `User`; `MonthlyReportRemark` belongsTo `Client` and creator `User`.
9. THE System SHALL implement PHP 8.1+ backed enums `Role`, `TicketStatus`, `Priority`, and `ClientStatus` and cast them on their respective model columns.
10. THE System SHALL enforce a no-hard-delete policy: the application code (routes, controllers, Livewire components, observers, and console commands) SHALL contain no `delete()` calls or delete routes targeting `tickets` or `clients` records.

---

### Requirement 5: Ticket Number Generation and Status History

**User Story:** As a support team member, I want every ticket to have a unique, human-readable number and a full status change audit trail, so that tickets can be referenced easily and their lifecycle is traceable.

#### Acceptance Criteria

1. WHEN a new ticket is created, THE `TicketObserver` SHALL invoke `TicketNumberService` to generate and assign a unique `ticket_number` before the record is persisted.
2. THE `TicketNumberService` SHALL generate ticket numbers in the zero-padded sequential format `TKT-NNNNN` (e.g., `TKT-00001`), incrementing by 1 for each new ticket, such that no two tickets share the same `ticket_number`.
3. IF `TicketNumberService` fails to generate a unique ticket number, THEN THE System SHALL abort ticket creation and not persist any partial record.
4. WHEN a ticket's `status` field changes, THE `TicketObserver` SHALL write a new row to `ticket_status_histories` capturing `old_status`, `new_status`, `notes` (nullable, only present when a reason is provided), `changed_by` (the authenticated user's id, or null for system-initiated transitions), and `changed_at`.
5. WHEN a ticket transitions to `TicketStatus::Resolved`, THE `TicketObserver` SHALL set the ticket's `resolved_at` timestamp to the current datetime.
6. WHEN a ticket transitions away from `TicketStatus::Resolved` to any other status, THE `TicketObserver` SHALL set the ticket's `resolved_at` to null.
7. THE `TicketObserver` SHALL be registered in a dedicated service provider so that creation and update side effects are not embedded in the `Ticket` model.

---

### Requirement 6: Client Management (Admin Only)

**User Story:** As an Admin, I want to create, edit, and deactivate client organisations, so that I can control which clients use the portal.

#### Acceptance Criteria

1. THE System SHALL provide a client list page at `/clients` accessible only to Admin, displaying all clients with their name, contact person, email, phone, and status.
2. WHEN an Admin submits the client creation form with valid data (name, contact_person, email, phone, and status all present and valid), THE System SHALL persist a new `Client` record and include it in the client list.
3. WHEN an Admin submits the client creation form with missing or invalid data, THE System SHALL return field-level validation errors without persisting the record, enforced via `StoreClientRequest`.
4. WHEN an Admin submits the client edit form with valid data, THE System SHALL update the existing `Client` record and reflect the changes in the client list.
5. WHEN an Admin submits the client edit form with missing or invalid data, THE System SHALL return field-level validation errors without modifying the record, enforced via `UpdateClientRequest`.
6. WHEN an Admin sets a client's status to `ClientStatus::Inactive`, THE System SHALL persist the `inactive` status without deleting the `Client` record.
7. WHEN an Admin sets a client's status to `ClientStatus::Active`, THE System SHALL persist the `active` status on the `Client` record.
8. IF a non-Admin user requests any route under `/clients`, THEN THE System SHALL return a 403 Forbidden response.

---

### Requirement 7: User Management (Admin Only)

**User Story:** As an Admin, I want to create, edit, and deactivate user accounts, so that I can control who has access to the portal.

#### Acceptance Criteria

1. IF an Admin requests `/users`, THEN THE System SHALL display a user list page showing all users with their name, email, role, and active status.
2. WHEN an Admin submits the user creation form with valid data (name, email, password, role, and `client_id` when role is `Role::Client`), THE System SHALL persist a new `User` record with the password hashed using bcrypt.
3. WHEN an Admin submits the user creation form with `role = Role::Client` and no `client_id`, THE System SHALL return a validation error indicating `client_id` is required and not persist the record, enforced via `StoreUserRequest`.
4. WHEN an Admin submits the user creation form with `role = Role::Client` and a valid `client_id`, THE System SHALL persist the `User` record with the correct `client_id` association.
5. WHEN an Admin submits the user creation form with a duplicate email address, THE System SHALL return a validation error indicating the email is already taken and not persist the record.
6. WHEN an Admin submits the user edit form with valid data, THE System SHALL update the `User` record without rendering the stored password value in any view or HTTP response.
7. IF the user edit form is submitted without a password field, THE System SHALL leave the existing password unchanged.
8. WHEN an Admin sets a user's active status to inactive, THE System SHALL set `is_active = false` without deleting the `User` record.
9. WHEN an Admin sets a user's active status to active, THE System SHALL set `is_active = true` on the `User` record.
10. IF a non-Admin user requests any route under `/users`, THEN THE System SHALL return a 403 Forbidden response.

---

### Requirement 8: Ticket Module

**User Story:** As a user, I want to create, view, and manage support tickets according to my role, so that client issues are tracked and resolved efficiently.

#### Acceptance Criteria

1. WHEN an Admin submits the ticket creation form with valid data, THE System SHALL persist a new `Ticket` record with `status = TicketStatus::Open` and a `ticket_number` assigned by `TicketNumberService`.
2. WHEN a Client_User submits the ticket creation form with valid data, THE System SHALL persist a new `Ticket` record scoped to the Client_User's `client_id`, with `status = TicketStatus::Open` and a `ticket_number` assigned by `TicketNumberService`.
3. WHEN any user submits the ticket creation form with missing or invalid data, THE System SHALL return validation errors without persisting the record, enforced via `StoreTicketRequest`.
4. IF an Engineer submits a ticket creation request, THEN THE System SHALL return a 403 Forbidden response regardless of the payload.
5. THE System SHALL display the ticket list filtered by `scopeVisibleTo` so that each role sees only the tickets they are permitted to view.
6. WHEN an Admin views the ticket list, THE System SHALL display all tickets across all clients.
7. WHEN an Engineer views the ticket list, THE System SHALL display only tickets where `assigned_engineer_id` matches the Engineer's user id.
8. WHEN a Client_User views the ticket list, THE System SHALL display only tickets where `client_id` matches the Client_User's `client_id`.
9. THE System SHALL provide ticket list filtering by `status` and `priority` for all roles, and additionally by `client_id` for Admin only, applied through `TicketQueryService`.
10. WHEN an Admin updates a ticket via the edit form with valid data, THE System SHALL allow changes to `status`, `assigned_engineer_id`, and `priority`, enforced via `UpdateTicketRequest`.
11. WHEN an Engineer updates a ticket assigned to them, THE System SHALL allow changes to `status` only and SHALL return a 403 Forbidden response for any attempt to change other fields.
12. IF a Client_User submits a request to change a ticket's `priority`, THEN THE System SHALL return a 403 Forbidden response regardless of UI state.
13. IF a Client_User submits a request to change a ticket's `status`, THEN THE System SHALL return a 403 Forbidden response regardless of UI state.
14. WHEN an Admin adds a comment to a ticket, THE System SHALL allow the comment to be saved with `is_internal = true` or `is_internal = false`.
15. WHEN an Engineer adds a comment to a ticket assigned to them, THE System SHALL allow the comment to be saved with `is_internal = true` or `is_internal = false`.
16. WHEN a Client_User adds a non-empty comment to a ticket belonging to their Client, THE System SHALL persist the comment with `is_internal = false` and SHALL return a 403 Forbidden response for any payload that sets `is_internal = true`.
17. WHILE a user is viewing a ticket detail page, THE System SHALL display internal_comments only to users with `role = Role::Admin` or `role = Role::Engineer`.
18. WHEN a ticket detail page is loaded, THE System SHALL display the ticket's `ticket_status_histories` entries in chronological order, showing `old_status`, `new_status`, `notes`, and `changed_at` for each entry.
19. WHEN a ticket's `status` is changed via the edit form, THE System SHALL route the change through `TicketObserver` so that a new `TicketStatusHistory` row is persisted.

---

### Requirement 9: Seed Data

**User Story:** As a developer, I want a complete set of seed data, so that I can demonstrate and test all role scenarios immediately after setup.

#### Acceptance Criteria

1. WHEN `php artisan migrate:fresh --seed` is executed, THE System SHALL create one Admin user with email `admin@example.com` and password `password123`.
2. WHEN `php artisan migrate:fresh --seed` is executed, THE System SHALL create two Engineer users with emails `engineer1@example.com` and `engineer2@example.com`, each with password `password123`.
3. WHEN `php artisan migrate:fresh --seed` is executed, THE System SHALL create two Client company records named "Diskominfo Klungkung" and "PT Example Manufacturing".
4. WHEN `php artisan migrate:fresh --seed` is executed, THE System SHALL create two Client_User accounts with emails `client1@example.com` and `client2@example.com` (password `password123`), associated with "Diskominfo Klungkung" and "PT Example Manufacturing" respectively.
5. WHEN `php artisan migrate:fresh --seed` is executed, THE System SHALL create at least 10 tickets distributed across both Client companies and both Engineers, with at least one ticket for each `TicketStatus` value (`Open`, `InProgress`, `Resolved`, `Closed`) and at least one ticket for each `Priority` value (`Low`, `Medium`, `High`).
6. WHEN `php artisan migrate:fresh --seed` is executed, THE System SHALL create at least one public_comment and at least one internal_comment on each of at least three distinct seeded tickets.

---

### Requirement 10: Dashboards

**User Story:** As any authenticated user, I want a role-specific dashboard showing my relevant ticket metrics and a status breakdown chart, so that I can understand the current state of support at a glance.

#### Acceptance Criteria

1. WHEN an Admin loads the dashboard, THE System SHALL display: total ticket count grouped by each defined `TicketStatus` value, total count of tickets with `status = Open`, total count of tickets with `assigned_engineer_id = null`, and a status breakdown chart.
2. WHEN an Engineer loads the dashboard, THE System SHALL display: count of tickets assigned to that Engineer grouped by each defined `TicketStatus` value, and a status breakdown chart scoped to that Engineer's tickets.
3. WHEN a Client_User loads the dashboard, THE System SHALL display: count of tickets belonging to the Client_User's Client grouped by each defined `TicketStatus` value, and a status breakdown chart scoped to that Client's tickets.
4. THE System SHALL display the 5 most recently updated tickets visible to the authenticated user on each dashboard, ordered by `updated_at` descending, scoped using the same visibility rules as that role's metrics.
5. WHEN a ticket's status is updated, THE System SHALL reflect the updated ticket counts, status groupings, and status breakdown chart data on the dashboard upon the next full page load or Livewire component refresh.
6. THE System SHALL compute all dashboard metrics from live aggregate database queries at the time of each page load or Livewire refresh, with no hardcoded metric values in any view, component, or service class.

---

### Requirement 11: Monthly Report

**User Story:** As an Admin or Client_User, I want to view a monthly report of ticket activity for a client, so that I can review support performance over a given period.

#### Acceptance Criteria

1. WHEN an Admin selects a client, a month (1–12), and a year on the monthly report page, THE System SHALL display a report containing: total ticket count, ticket counts grouped by each `TicketStatus` value, ticket counts grouped by each `Priority` value, a list of tickets where `created_at` falls within that calendar month and year, and the saved `MonthlyReportRemark` for that `(client_id, month, year)` if one exists.
2. WHEN a Client_User loads the monthly report page, THE System SHALL display the report scoped to the Client_User's own Client and SHALL present no control to select a different client, while still allowing the Client_User to select month and year.
3. WHEN an Admin saves a remark (up to 1,000 characters) for a `(client_id, month, year)` combination where a `MonthlyReportRemark` already exists, THE System SHALL update the existing record rather than inserting a duplicate.
4. WHEN an Admin saves a remark for a `(client_id, month, year)` combination where no `MonthlyReportRemark` exists, THE System SHALL create a new `MonthlyReportRemark` record.
5. WHEN the selected month and year contain no tickets for the client, THE System SHALL display a report with all counts set to zero rather than returning an error.
6. IF a month value outside the range 1–12 or a year value outside a reasonable range is submitted, THEN THE System SHALL return a validation error without rendering a report.
7. WHEN a user requests the print view at `/reports/monthly/print`, THE System SHALL render a layout that contains only the report content, with no navigation or sidebar elements present in the response.
8. WHEN an Admin saves a remark successfully, THE System SHALL display a confirmation to the Admin. IF the save fails, THEN THE System SHALL display an error message without discarding the entered remark text.
9. THE `MonthlyReportService` SHALL source all ticket data via `TicketQueryService` to ensure scoping is consistent with the rest of the application.

---

### Requirement 12: Service and Observer Architecture

**User Story:** As a developer, I want the application's business logic to be centralised in well-defined service classes and observers, so that the codebase is maintainable and logic is not duplicated across Livewire components.

#### Acceptance Criteria

1. THE System SHALL implement a `TicketQueryService` class that exposes a single authoritative method for building role-scoped, filterable `Ticket` queries, consumed by all services and Livewire components that need ticket data. Role-scoped means: all tickets for Admin, assigned tickets only for Engineer, and own-client tickets only for Client_User.
2. THE System SHALL implement a `DashboardMetricsService` class whose query foundation is `TicketQueryService`.
3. THE System SHALL implement a `MonthlyReportService` class whose query foundation is `TicketQueryService`.
4. THE System SHALL implement a `TicketNumberService` class. WHEN a ticket is about to be created, THE `TicketObserver` SHALL call `TicketNumberService` to generate the ticket number; THE `TicketNumberService` SHALL NOT be called directly from Livewire components or controllers.
5. THE System SHALL implement a `TicketObserver` registered in a dedicated `TicketObserverServiceProvider` that handles ticket-number assignment on the `creating` event and status-history logging on the `updating` event. WHEN the `updating` event fires and the `status` field has changed, THE Observer SHALL write a new row to `ticket_status_histories`.
6. THE System SHALL implement Form Request classes (`StoreTicketRequest`, `UpdateTicketRequest`, `StoreClientRequest`, `UpdateClientRequest`, `StoreUserRequest`, `UpdateUserRequest`) that encapsulate all validation and authorization for their respective actions, rather than performing inline validation in Livewire components.

---

### Requirement 13: Code Quality, Testing, and Documentation

**User Story:** As a developer, I want a green Pest test suite and a complete README, so that the application can be verified and onboarded by any reviewer.

#### Acceptance Criteria

1. THE System SHALL have Pest v4 feature tests for each role (Admin, Engineer, Client_User) covering both permitted and denied access for the following actions on every module: list, view, create, edit, and comment on tickets; list and edit clients; list and edit users; and view monthly reports.
2. WHEN a Client_User submits a direct HTTP POST request to change a ticket's `priority` field, THE System SHALL return a 403 HTTP response, and a Pest feature test SHALL assert this behaviour.
3. WHEN an Engineer submits a direct HTTP POST request to change a ticket's `assigned_engineer_id` or `priority` field, THE System SHALL return a 403 HTTP response, and a Pest feature test SHALL assert this behaviour.
4. WHEN a non-Admin role (Engineer or Client_User) requests an Admin-only route (`/clients` or `/users`), THE System SHALL return a 403 HTTP response, and a Pest feature test SHALL assert this behaviour for each Admin-only route and each non-Admin role.
5. WHEN `php artisan test --compact` is executed, THE System SHALL complete with all tests passing and zero failures.
6. THE System SHALL have a `README.md` covering: architecture overview, ERD or Mermaid diagram, folder structure, Docker setup instructions, seed credentials, role summary, known limitations, and possible future improvements.
7. THE System SHALL never log or render a raw password value in any view, log entry, or HTTP response.
8. THE System SHALL have no application-layer delete route and no Eloquent `delete()` call targeting `tickets` or `clients` records.
