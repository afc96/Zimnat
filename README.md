# PolicyPilot - Policy Renewal Reminder System

PolicyPilot is a lightweight internal web application for Zimnat Life Assurance staff to manage policies, renewal dates, supporting documents, and role-based access.

## Stack

- PHP 8.4+
- MySQL 8.4 LTS
- HTML5, CSS3, vanilla JavaScript
- PDO prepared statements
- OOP PHP with a lightweight MVC-style structure

## Setup

1. Install PHP 8.4+ and MySQL 8.4 LTS.
2. Create the database and seed demo data:

```bash
mysql -u root -p < database/schema.sql
```

3. Optional: configure database credentials using environment variables:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_NAME=zimnat_policy_renewal
export DB_USER=root
export DB_PASS=
```

You can also copy `.env.example` to `.env` and adjust values locally.

4. Start the local PHP server:

```bash
php -S localhost:8000 -t public
```

5. Open:

```text
http://localhost:8000
```

## Verification

For reviewer traceability, see `ASSESSMENT.md`. For implemented security controls and production notes, see `SECURITY.md`.

Supporting documentation:

- `SUBMISSION_NOTES.md` - reviewer handoff, design choices, and verification evidence.
- `CODING_STANDARDS.md` - project coding, security, naming, validation, and testing conventions.
- `TESTING.md` - local test commands, smoke coverage, and manual reviewer checklist.
- `docs/adr/0001-modular-php-mvc.md` - architecture decision record for the modular PHP structure.

Run the end-to-end smoke test against a running local server:

```bash
APP_URL=http://127.0.0.1:8000 php tools/smoke.php
```

The smoke runner logs in with each seeded role, creates and edits a temporary user, creates and edits a temporary policy, uploads/downloads/deletes a PDF document, updates a reminder, verifies CSV exports, checks viewer access restrictions, and cleans up the temporary records.

For an existing database created before the dedicated client register was added, apply:

```bash
mysql -u root -p zimnat_policy_renewal < database/migrations/2026_05_15_clients_table.sql
```

After that, use the migration runner for tracked schema changes:

```bash
php tools/migrate.php
```

If you are attaching the runner to an already-upgraded local database, baseline the earlier manual migrations once:

```bash
php tools/migrate.php --baseline-through=2026_05_15_clients_table.sql
```

Fast local gates:

```bash
php tools/lint.php
php tools/test.php
php tools/security_gate.php
```

## Demo Accounts

All seeded accounts use the password `password`.

- `admin@zimnat.test` - Admin
- `officer@zimnat.test` - Policy Officer
- `viewer@zimnat.test` - Viewer

## Project Structure

```text
app/
  Controllers/   Request handling and role gates
  Core/          Database, auth, CSRF, view rendering
  Models/        OOP data access and dashboard queries
  Support/       Validation and helper functions
  Views/         Server-rendered UI templates
config/          Application and database configuration
database/        MySQL schema and seed data
public/          Web root, router, CSS, JavaScript
storage/         Uploaded files and runtime storage
```

## Role Design

- Admin: manages users, views all policies/documents, updates policies, uploads documents, accesses dashboard data.
- Policy Officer: creates and updates policies, removes policies, uploads documents, tracks renewal status.
- Viewer: read-only access to dashboard, policy listings, policy details, and documents.

Role checks are enforced server-side in controllers. UI controls are also hidden where appropriate, but hidden buttons are not treated as security.

## Implemented Assessment Features

- Authentication with secure password hashing.
- Role-based access control.
- Policy CRUD for Admin and Policy Officer.
- Read-only Viewer experience.
- Server-side search, filtering, sorting, and pagination for policy listings.
- Server-side document search and pagination.
- Dashboard KPIs for total policies, active policies, expired policies, policies nearing renewal, and missing documents.
- Dedicated Renewals workspace with a personal queue and a full reminder register for upcoming and overdue renewal work.
- Reminder workflow actions: contacted, snoozed, failed, and resolved.
- Personal assigned-work queue surfaced as the primary Renewals navigation item.
- Saved operational views for expiring, overdue, missing-document, failed-contact, and snoozed work.
- Bulk actions for selected policies and reminder work.
- CSV exports for policy register and renewal reminder queues.
- Policy audit metadata showing creation owner, last editor, and last reminder contact.
- Policy timeline events linked to the related policy record.
- Document checklist with typed expected renewal documents.
- Tabbed client profile modal with overview, portfolio snapshot, edit flow, total premium, document count, and reminder state.
- Admin Settings module with Users, Roles & Permissions, System Activity, Reminder Rules, and Document Checklist tabs.
- Dynamic roles, permission matrix, configurable reminder rules, and configurable document types.
- Dedicated client register with reusable client profiles linked to policies by `client_id`.
- Standalone client management for creating, editing, and archiving client profiles.
- Connected client-to-policy workflow: create/select a client, add a policy from that client, then move into document upload and reminder follow-up.
- Deep links between clients, policies, documents, renewals, reminder records, and dashboard KPI cards.
- Soft-delete archival for users, policies, and clients so business records are traceable.
- Transactional service layer for policy, client, user, document, and reminder workflows.
- Structured audit details for sensitive changes such as policy edits, user changes, reminder updates, document deletion, and client updates.
- Supporting document uploads per policy.
- File upload validation for JPG, PNG, and PDF files.
- Login rate limiting for repeated failed attempts.
- Global security headers including CSP, frame denial, referrer policy, permissions policy, and MIME sniffing protection.
- CSRF protection for state-changing requests.
- PDO prepared statements for SQL injection protection.
- Input validation on policy and user forms.
- Focused local test runner for validation, client, policy, and soft-delete behavior.
- Quick security gate for common local regressions.
- Basic error handling and friendly error pages.
- Vanilla JavaScript enhancements for filters, upload dropzone, theme toggle, confirmations, toast messages, print/export actions, and form working states.
- Row-driven detail modals for policies, documents, clients, and users.
- Logged renewal notifications when reminder contact actions are recorded, with optional mail/SMS transports.

## Notification Configuration

Reminder contact actions are notification-aware. By default notifications are written to `storage/logs/notifications.log`, which keeps the assessment runnable without external services.

Optional environment variables:

```bash
export NOTIFICATION_EMAIL_TRANSPORT=mail
export NOTIFICATION_EMAIL_FROM=noreply@example.com
export SMS_WEBHOOK_URL=https://example.com/sms-webhook
```

When `NOTIFICATION_EMAIL_TRANSPORT=mail`, PHP's configured `mail()` transport is used for client email reminders. When `SMS_WEBHOOK_URL` is set, SMS payloads are posted as JSON.

## Assumptions

- This is an internal staff system, not a public customer portal.
- A policy can have many supporting documents.
- Policies nearing renewal are policies with a renewal date from today through the next 30 days.
- Client records own contact/profile details; policies own policy terms, premium frequency, assigned staff member, and reminder lead time.
- Uploaded documents are stored outside the public web root and downloaded through an authenticated controller action.
- Monetary values are displayed in USD for demo purposes; currency can be adjusted later.

## AI Usage Disclosure

AI assistance was used to help plan the architecture, scaffold the PHP/MySQL implementation, design the UI, and draft documentation. The final code and design decisions were reviewed against the assessment requirements: OOP, role-based access control, database design, validation, upload safety, and maintainability.
