# PolicyPilot Assessment Trace

This document maps PolicyPilot against the pre-interview requirements and gives reviewers a fast, repeatable path through the product.

Related handoff documents:

- `SUBMISSION_NOTES.md`
- `SECURITY.md`
- `CODING_STANDARDS.md`
- `TESTING.md`
- `docs/adr/0001-modular-php-mvc.md`

## Stack

- PHP 8.4+ with object-oriented controllers, models, services, and support classes.
- MySQL 8.4 LTS with migrations, seed data, foreign keys, indexes, and soft-delete fields.
- HTML, CSS, and vanilla JavaScript.
- PDO prepared statements for all database writes and reads that accept user input.

## Demo Accounts

All seeded accounts use `password`.

- Admin: `admin@zimnat.test`
- Policy Officer: `officer@zimnat.test`
- Viewer: `viewer@zimnat.test`

## Reviewer Demo Flow

1. Log in as Admin and confirm the dashboard shows total, active, expired, renewal, and missing-document metrics.
2. Open the profile menu, then Admin Settings. Create a user, edit the user, lock/deactivate the user, and review system activity.
3. Open Clients. Create a client profile, open the row modal, review the profile tabs, then add a policy from the client workflow.
4. Open Policies. Create or edit a policy, assign an officer, set renewal dates, and confirm server-side search, filters, sorting, pagination, and CSV export.
5. Open a policy detail. Upload a JPG, PNG, or PDF supporting document, preview/download it, then delete it if needed.
6. Open Documents. Search, filter, sort, export, preview, and delete documents through the row modal.
7. Open Renewals. Work the assigned queue, open the full reminder register when needed, mark a client contacted, snooze a reminder, fail a contact attempt, and resolve a reminder.
8. Log in as Viewer and confirm read-only access: dashboard, policy listing/details, documents, and clients are visible, but create/update/delete controls are unavailable server-side.
9. Log in as Policy Officer and confirm operational access: policy and document workflows are available, but Admin Settings are not.

## Requirement Mapping

| Requirement | Implementation evidence |
| --- | --- |
| Add and manage insurance policies | Policy register, policy detail/edit workflow, `PolicyController`, `PolicyService`, `Policy` model. |
| Track renewal dates | Renewal date fields, renewal status calculation, Renewals queue, full reminder register, dashboard KPIs. |
| View policies close to expiry | Expiring dashboard card, Renewals workspace, filtered task views, server-side date filters. |
| Upload supporting documents | Policy detail upload panel, `DocumentController`, `DocumentService`, authenticated download/preview/delete. |
| View summary insights on policies | Dashboard KPI cards, document compliance card, renewal queue, latest policies, admin performance review. |
| Admin responsibilities | Admin Settings users, roles and permissions, system activity, reminder rules, document checklist. |
| Policy Officer responsibilities | Policy create/update/delete, document upload/manage, renewal tracking, assigned task queue. |
| Viewer responsibilities | Read-only dashboard, policies, documents, and clients with server-side action restrictions. |
| Authenticate users before access | `AuthController`, session auth, controller `requireAuth` gates. |
| Restrict features based on role | Controller-level `requireRole`/`can` checks plus role-aware UI controls. |
| Prevent unauthorized actions | Server-side checks on create/update/delete/upload/export actions. |
| Policy fields | Policy number, client, insurance type, premium, start date, renewal date, status, assigned officer, reminders, contact metadata. |
| Policy statuses | Active, Expired, Pending Renewal, plus operational reminder states. |
| Allowed file types | JPG, PNG, and PDF validation in upload service and form hints. |
| Store files on server and link to policy | Files are stored under `storage/uploads` and linked through `policy_documents`. |
| PHP 8+, MySQL, HTML/CSS/JS | Documented stack and runnable local app. |
| OOP and separation of concerns | Controllers handle requests, services handle workflows, models handle persistence, support classes handle validation/helpers. |
| Input validation | Form validators for auth, users, clients, policies, documents, reminders, settings, and account updates. |
| Basic error handling | Flash messages, friendly error pages, guarded controller flows, duplicate handling. |
| SQL injection protection | PDO prepared statements throughout models and services. |
| Security hardening | CSRF, output escaping, login rate limiting, safe upload handling, authenticated file serving, secure session flags, and browser security headers. |
| Maintainability | Migrations, seed data, focused services, test tools, security gate, smoke runner, and reviewer trace. |

## Additional Product Quality Features

- Dedicated client register linked to policies by `client_id`, avoiding duplicated client entry.
- Client-to-policy workflow so staff can create a client and continue straight into policy creation.
- Deep links between dashboard cards, clients, policies, documents, renewals, and reminder records.
- Server-side search, filters, sorting, and pagination across high-volume tables.
- CSV export menus for operational registers.
- Row-driven detail modals instead of action-column clutter.
- Configurable roles, permissions, reminder rules, and document checklist.
- Audit trail for user, client, policy, document, and reminder changes.
- Soft delete/archive behavior for business traceability.
- In-app account settings for profile and password updates.
- Dark mode, accessible focus states, and responsive UI refinements.

## Local Verification

Run these fast gates:

```bash
php tools/lint.php
php tools/test.php
php tools/security_gate.php
```

Run the browser-backed smoke pass after starting the local server:

```bash
APP_URL=http://127.0.0.1:8000 php tools/smoke.php
```

The smoke runner logs in with all seeded roles, creates and edits temporary records, uploads and deletes a document, verifies exports, checks viewer restrictions, and cleans up its temporary data.
