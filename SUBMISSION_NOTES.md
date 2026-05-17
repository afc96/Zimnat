# Submission Notes

PolicyPilot was built as a complete internal policy renewal workbench for the Zimnat Life Assurance assessment. The implementation intentionally stays within the requested PHP, MySQL, HTML, CSS, and JavaScript stack while adding enough workflow depth to feel like a usable staff tool rather than a minimal CRUD demo.

## What To Review First

1. `README.md` for setup, stack, demo users, and verification commands.
2. `ASSESSMENT.md` for requirement-by-requirement traceability.
3. `SECURITY.md` for implemented controls and production deployment notes.
4. `CODING_STANDARDS.md` for code organization, naming, validation, and UI conventions.
5. `docs/adr/0001-modular-php-mvc.md` for the main architecture decision.

## Design Choices

- The app uses a small front controller in `public/index.php` so all requests pass through one routing and security entry point.
- Controllers own request/response flow and permission gates.
- Services own multi-step business workflows such as creating policies, uploading documents, updating reminders, and writing audit records.
- Models own persistence and query composition using PDO prepared statements.
- Views are server-rendered PHP templates with minimal vanilla JavaScript for progressive interaction.
- Client records are first-class entities linked to policies by `client_id`, avoiding repeated client data entry.
- File uploads are stored outside the public web root and served through authenticated controller actions.
- Role-based access is enforced server-side. Hidden UI controls are treated as convenience only, not security.

## Assessment Highlights

- Admin can manage users, roles, settings, documents, clients, policies, and activity.
- Policy Officers can manage policies, documents, reminders, clients, and their assigned task queue.
- Viewers have read-only access to dashboards and registers.
- Tables support server-side search, filters, sorting, pagination, row-driven modals, and CSV export.
- Supporting documents support upload, in-app preview, download, checklist status, and deletion.
- The dashboard changes by role so users only see operational content that makes sense for their responsibility.
- The main header keeps one renewal entry, `Renewals`, while the full reminder register remains available contextually for staff who need broader oversight.

## Verification Evidence

Latest local verification completed on 2026-05-16:

```text
LINT_PASSED 53 files
SECURITY_GATE_PASSED
TESTS_PASSED
SMOKE_PASSED
```

Commands:

```bash
php tools/lint.php
php tools/security_gate.php
php tools/test.php
php -S 127.0.0.1:8000 -t public
APP_URL=http://127.0.0.1:8000 php tools/smoke.php
```

The smoke runner logs in as seeded users, creates and edits records, verifies viewer restrictions, uploads/downloads/deletes a document, checks reminder actions, validates CSV exports, and cleans up temporary data.

In this local verification pass the running server was available at `http://127.0.0.1:8080`, so the smoke suite was run as:

```bash
php tools/smoke.php http://127.0.0.1:8080
```

## Known Production Follow-Ups

- Enable HTTPS and set `APP_SECURE_COOKIES=true`.
- Replace seeded demo users/passwords before any real deployment.
- Use a least-privilege MySQL user rather than a local root account.
- Configure a production web server so only `public/` is web-accessible.
- Decide the real notification transport for email/SMS reminders.
- Add formal backup and retention policies for uploaded documents and audit logs.
