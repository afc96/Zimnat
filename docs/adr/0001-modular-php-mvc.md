# ADR-0001: Use A Modular PHP MVC-Style Monolith

## Status

Accepted

## Date

2026-05-15

## Context

The assessment requires a PHP 8+, MySQL, HTML/CSS, and JavaScript system with object-oriented design, role-based access control, validation, upload safety, and maintainable separation of concerns. The scope is a staff-facing policy renewal reminder system, not a distributed product or public customer portal.

The app needs enough real workflow depth to support policies, clients, documents, reminders, users, roles, audit activity, and reporting without adding framework dependencies that would distract from the assessment requirements.

## Decision Drivers

- Keep the stack simple and aligned with the brief.
- Make code structure easy for reviewers to inspect.
- Enforce security controls consistently.
- Preserve maintainability as features grow.
- Avoid over-engineering for a small internal workflow application.

## Considered Options

1. Single-file PHP pages with inline SQL and HTML.
2. Full framework implementation such as Laravel or Symfony.
3. Lightweight modular PHP MVC-style monolith.

## Decision

Use a lightweight modular PHP MVC-style monolith.

The app uses `public/index.php` as a front controller, controller classes for request flow and authorization, service classes for workflows, model classes for persistence, support classes for shared validation/helpers, and PHP templates for server-rendered views.

## Consequences

### Positive

- Clear separation of concerns without requiring a framework installation.
- Easy for assessors to inspect OOP structure, SQL access, validation, and role checks.
- A single deployable app fits the internal staff-system scope.
- Services create natural homes for transactions, audit logging, and cross-table workflows.

### Negative

- The app does not inherit framework-provided features such as routing middleware, ORM migrations, queues, or built-in validation.
- More responsibility sits on local conventions and documentation.
- Future growth may eventually justify adopting a framework.

### Neutral

- The current approach favors explicit code over convention-heavy framework behavior.
- Vanilla JavaScript is enough for the current UI, but complex future interactions may need stronger frontend structure.

## Migration Plan

- Keep all new routes entering through `public/index.php`.
- Add new workflows through controller/service/model boundaries.
- Add schema changes as SQL migrations under `database/migrations`.
- Add tests to `tools/test.php` for behavior-level regressions and `tools/smoke.php` for end-to-end flow coverage.

## Rollback Plan

If the modular structure becomes too limiting, migrate incrementally to a framework by preserving the current model/service boundaries as application services and moving controllers/views into the framework layer.

## Validation and Success Metrics

- `php tools/lint.php` passes.
- `php tools/security_gate.php` passes.
- `php tools/test.php` passes.
- `APP_URL=http://127.0.0.1:8000 php tools/smoke.php` passes.
- Reviewers can trace each assessment requirement to a controller, service, model, view, and database table.

## Related Artifacts

- `README.md`
- `ASSESSMENT.md`
- `SECURITY.md`
- `CODING_STANDARDS.md`
- `TESTING.md`
