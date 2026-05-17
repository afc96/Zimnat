# Security Notes

PolicyPilot includes the security controls expected for the assessment scope and a few additional hardening measures for a realistic internal staff tool.

## Implemented Controls

- Authentication uses PHP password hashing and session regeneration after login.
- Login attempts are rate-limited by client IP and email using a file-backed limiter under `storage/rate_limits`.
- Sessions use `HttpOnly` and `SameSite=Lax` cookies. `APP_SECURE_COOKIES=true` should be enabled behind HTTPS.
- Server-side role checks protect admin, policy, document, reminder, user, client, and settings actions.
- State-changing requests are POST-only and protected by CSRF tokens.
- Database access uses PDO prepared statements for user-supplied values.
- Forms are validated server-side through shared validator classes.
- Output is escaped with `htmlspecialchars` through the `e()` helper.
- Uploads are limited to JPG, PNG, and PDF MIME types and a 5 MB size limit.
- Uploaded files are stored outside the public web root and served through authenticated controllers.
- Downloads and previews send `X-Content-Type-Options: nosniff`.
- Global browser hardening headers are sent: `Content-Security-Policy`, `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`, and `X-Content-Type-Options`.
- PHP version disclosure is removed from responses.
- Sensitive changes are recorded in the activity log.

## Verification Commands

```bash
php tools/lint.php
php tools/test.php
php tools/security_gate.php
APP_URL=http://127.0.0.1:8000 php tools/smoke.php
```

The test suite includes a regression assertion for login rate limiting. The smoke suite verifies role access, viewer restrictions, uploads, downloads, exports, reminders, and cleanup.

## Production Deployment Notes

- Serve the app over HTTPS and set `APP_SECURE_COOKIES=true`.
- Point the web server document root at `public/` only.
- Keep `storage/`, `database/`, `config/`, and `.env` outside public access.
- Use a least-privilege MySQL user instead of a root database account.
- Rotate seeded demo passwords or remove demo users before production use.
- Put logs and uploads on backed-up storage with suitable retention policies.
