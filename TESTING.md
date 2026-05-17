# Testing Guide

PolicyPilot includes three local verification layers: static syntax checks, a focused DB-backed test runner, and an end-to-end smoke runner.

## Fast Checks

```bash
php tools/lint.php
php tools/security_gate.php
```

- `tools/lint.php` runs PHP syntax checks across the project.
- `tools/security_gate.php` checks common local security regressions such as missing CSRF usage, unsafe upload patterns, missing prepared statements in sensitive areas, and required hardening files.

## DB-Backed Regression Tests

```bash
php tools/test.php
```

Current coverage:

- Rate limiter blocks repeated attempts.
- Policy validator catches invalid input.
- Client service creates standalone clients.
- Policy service links policies to clients.
- Policy delete is soft and hidden from normal reads.

These tests require MySQL to be running and configured through `.env` or environment variables.

## End-To-End Smoke Test

Start the app:

```bash
php -S 127.0.0.1:8000 -t public
```

Run the smoke suite:

```bash
APP_URL=http://127.0.0.1:8000 php tools/smoke.php
```

The smoke suite verifies:

- Admin login.
- Admin user create, search, edit, and cleanup.
- Client create, search, edit, archive, and client-to-policy redirect.
- Viewer read-only restriction for policy creation.
- Policy Officer policy create and edit.
- Document upload, search, download, and delete.
- Reminder contact action.
- Policy, renewal reminder, client, and document CSV exports.
- Cleanup of temporary smoke records.

## Manual Reviewer Checklist

Use the seeded accounts from `README.md`.

1. Admin: review dashboard, settings tabs, user management, roles, audit, reminder rules, and document checklist.
2. Policy Officer: open Renewals, create a client, add a policy, upload a document, and update an assigned reminder.
3. Viewer: confirm read-only access and absence of mutation controls.
4. Tables: verify search, filters, sorting, pagination, export menu, and row modals.
5. Security: try a forbidden viewer action such as `?page=policy_new` and confirm a `403` response.

## Navigation Notes

- The header exposes one renewal entry: `Renewals`.
- `Renewals` opens the signed-in staff member's assigned queue.
- The full reminder register is still available from the Renewals page, dashboard cards, and policy/client/document deep links for operational oversight.
