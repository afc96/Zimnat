# Coding Standards

These standards describe the conventions used in PolicyPilot and should be followed for future changes.

## Architecture

- Keep `public/index.php` as the only public entry point.
- Put request routing, authentication checks, and controller dispatch at the edge.
- Keep controllers thin: validate access, call services/models, set flash messages, and redirect/render.
- Put multi-step workflows in services, especially when a change touches more than one table or writes audit records.
- Put database reads/writes in models and use prepared statements for values supplied by users.
- Keep views presentation-focused. Avoid business rules in templates beyond display decisions.

## Security

- Require authentication for every non-login page and action.
- Enforce permissions server-side through controller guards.
- Protect every POST action with `Csrf::verify()` and `Csrf::field()`.
- Escape user-supplied output with `e()`.
- Use allowlists for status, role, sort, MIME type, and enum-like inputs.
- Store uploaded files outside `public/` and serve them through authenticated actions.
- Do not expose stack traces outside local development.
- Do not commit real credentials, API keys, production `.env` files, or uploaded customer documents.

## Naming

- Use domain terms consistently: policy, client, reminder, document, user, role, permission, activity.
- Prefer explicit method names such as `updateReminder`, `deleteClient`, and `exportDocuments`.
- Use `*_id` only for database identifiers and route parameters; use `policy_number` for the business-facing policy reference.
- Keep boolean names readable: `is_active`, `is_required`, `canEdit`, `previewable`.
- Avoid vague names such as `data`, `info`, `manager`, and `helper` unless they match an existing local convention.

## Validation

- Add validation in `App\Support\Validator` when a form accepts user input.
- Validate both presence and allowed values for select fields.
- Validate dates using `Y-m-d` and enforce domain rules such as renewal date after start date.
- Validate numeric fields with sensible bounds, not only numeric type checks.
- Reuse validation rules across create and update flows where possible.

## Database

- Add schema changes through migrations in `database/migrations`.
- Keep foreign keys and indexes aligned with workflow queries.
- Prefer soft delete for business records that need auditability.
- Keep seed data realistic enough to demonstrate workflows.
- Do not concatenate user-supplied values into SQL. For dynamic sort columns, use controller/model allowlists.

## Views and JavaScript

- Prefer server-rendered HTML with small JavaScript enhancements.
- Use row-driven modals instead of repeated action columns where a table is dense.
- Keep buttons in modal footers for consistent interaction.
- Keep form controls visually consistent: same height, radius, and spacing for inputs and selects.
- Use icons where they reduce noise, but keep accessible labels or visible text where needed.
- Keep JavaScript behavior progressive: the page should still submit valid server forms.

## Comments

- Comment non-obvious intent, security reasoning, and cross-module constraints.
- Do not comment obvious mechanics.
- If a decision affects architecture or security beyond one file, document it in an ADR or `SECURITY.md`.

## Testing

- Run these before submission:

```bash
php tools/lint.php
php tools/security_gate.php
php tools/test.php
APP_URL=http://127.0.0.1:8000 php tools/smoke.php
```

- Add regression coverage when changing authentication, authorization, validation, uploads, exports, or policy/client workflows.
- Keep smoke-test data temporary and self-cleaning.
