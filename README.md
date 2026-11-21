# CampusOS School Management System

A secure PHP 8.2+ MVC foundation for a multi-school management system. It uses PDO with native prepared statements, server-side sessions, CSRF validation, role/permission checks, tenant-aware examples, an indexed MySQL schema, and responsive Tailwind/Alpine views.

> **Scope and production readiness:** This repository delivers the requested core architecture, initial workflows, database model, bootstrap path, and policies. It is not a claim that every operational module is fully implemented or legally compliant out of the box. Admissions, timetable editing, grade entry/report cards, fee payment recording, and notification integrations require the product-specific workflows described below before production launch.

## Requirements

- PHP 8.2+ with `pdo_mysql`, `mbstring`, `json`, and `openssl` extensions
- MySQL 8.0+ or MariaDB 10.6+
- Composer 2
- ZIP support in the PHP CLI or an installed unzip/7-Zip utility for Composer package extraction
- Apache with `mod_rewrite`, or an equivalent web-server rewrite to `public/index.php`
- HTTPS in all environments carrying real personal data

## Setup

Run the dependency-free smoke checks after checkout with `php tests/smoke.php`; PHP syntax can be checked with `php -l` on the source files. Database-backed workflows should also be verified against a disposable MySQL/MariaDB database before deployment.

1. Create an empty database and a dedicated application DB account. Grant only the privileges needed to read/write the application schema; run schema installation with a separate migration/deployment account.
2. Copy `.env.example` to `.env`; configure the database host, name, username, and password. Do not commit `.env`.
3. Install dependencies with `composer install --no-dev --classmap-authoritative`. On XAMPP, enable `php_zip.dll` for the CLI PHP or provide a ZIP extractor.
4. Import `database/schema.sql` into the empty database using the deployment account.
5. Set a temporary unique bootstrap password in `SMS_BOOTSTRAP_PASSWORD`, then run:

   ```text
   php bin/create-admin.php "System" "Administrator" admin@example.edu
   ```

   The command creates exactly one platform super administrator, stores an Argon2id hash, and refuses to create another. Unset the temporary environment variable immediately.
6. Configure the web server document root to the `public/` directory. Do not expose the repository root, `.env`, `vendor/`, or `storage/` over HTTP.
7. Verify HTTPS, session cookies, database connectivity, backups, SMTP/notification integration, monitoring, and restore procedures before adding real student data.

For Apache, the repository `.htaccess` denies access to selected root files and private directories, and `public/.htaccess` routes non-file requests. Apply equivalent deny/rewrite rules when using Nginx or another server. The application session cookie is Secure only when HTTPS is detected; configure TLS termination to set the server's HTTPS state from a trusted proxy, and avoid trusting arbitrary forwarded headers.

## Directory structure

```text
app/
  Controllers/       Request handlers for authentication, attendance, fees, family/student dashboards
  Core/              PDO singleton, router, view renderer, CSRF, auth/RBAC, audit logger
  Services/          Transactional fee invoices, grading, and authorization-aware reads
  Views/             Escaped PHP templates with responsive Tailwind/Alpine UI
bin/                 CLI-only initial administrator bootstrap
database/schema.sql  MySQL/MariaDB schema and non-credential sample seed data
docs/policies/       Acceptable use, privacy/retention, backup/disaster recovery
public/              Web document root and front controller
storage/              Non-public application storage (configure server permissions)
docs/                Static GitHub Pages marketing preview
```

## GitHub Pages preview

GitHub Pages can publish the static design preview in [`docs/index.html`](docs/index.html). GitHub Pages does not execute PHP and does not provide MariaDB/MySQL, so it cannot host login, sessions, database operations, or server-side application routes. Use a PHP-capable host (for example XAMPP during local development, or a managed PHP/MySQL server for production) for the full system.

The included GitHub Actions workflow deploys the `docs/` preview to Pages. Repository Settings → Pages must use **GitHub Actions** as the build and deployment source after the first workflow run.

## Implemented workflows

- Login/logout with session ID rotation, status and `auth_version` validation, 30-minute inactivity timeout, generic failure messages, DB-backed throttling (five failures / 15-minute lock per email+IP hash), CSRF checks, and audit records.
- Profile name/phone updates and one-use, 30-minute password-reset links with hashed tokens, session invalidation, enumeration-resistant responses, and per-email/IP request throttling. Configure `MAILER_DSN`, `MAIL_FROM`, and HTTPS `APP_URL` before enabling password recovery.
- Five seeded roles and permission tables. The super administrator and school administrator receive the seeded administrative permissions; teachers receive attendance, academics, and reports. Student/guardian access is intentionally constrained to linked records in the example query.
- Student dashboard reads attendance, grades, and outstanding invoice summaries for the authenticated student's linked profile.
- Parent/guardian dashboard reads attendance, recent grades, and outstanding fees only for explicitly linked active students.
- Grading-scale service calculates percentage, letter grade, and GPA points from each school's configured ranges.
- Attendance roster and batch save for a teacher's assigned section or authorized administrator, with date/status validation, section membership checks, a transaction, and audit event.
- Fee invoice creation from an active fee structure with same-school validation, unique random invoice number, transaction, line item, and PDF download through Dompdf. The sample payment table is ready for an audited payment workflow but does not yet include a payment posting controller.
- Academic, admissions-history, guardian-link, staff attendance, exams/grades, fee, timetable, document metadata, notification-log, password-reset-token, and audit schema foundations.

## RBAC and tenancy

Roles alone do not authorize a record. Every query handling school data must additionally constrain by the authenticated school/branch and, for teachers, the allocation. Student and guardian reads must constrain through `students.user_id` or `student_guardians`. The sample attendance and invoice code demonstrates these checks; reproduce them in every new endpoint and test cross-school access explicitly. Never treat a client-supplied school, student, section, or invoice ID as proof of authorization.

`user_roles` supports expiring role grants. Role assignment and school scoping must be changed only through an audited administrator workflow; do not grant roles based on request parameters.

## Database notes

- The schema uses InnoDB, `utf8mb4`, foreign keys, explicit deletion rules, indexes, strict SQL mode, and checks for monetary/date/grade bounds.
- Cascades are used for dependent records such as section roster attendance and invoice line items. Financial invoices and payments restrict deletion to preserve accounting history. Audit actors become nullable if a user is purged.
- Seed data includes an example school/branch/class/section, academic calendar, roles/permissions, a grading scale, and synthetic demonstration student/attendance/grade/invoice records. It intentionally includes no user credentials or reusable default login/password.
- Use versioned migrations for future changes. Back up before migration and test the migration/rollback against a production-sized copy.

## Security and operations before production

1. Replace CDN-delivered Tailwind/Alpine/Font Awesome with pinned, locally built assets and a strict Content Security Policy. The CDN links are for the starter UI only.
2. Configure and verify the included SMTP-backed password-reset flow and add invitation delivery. The token table stores a hash, never a raw reset token; tokens must be random, one-use, short-lived, and rate-limited.
3. Implement admission/profile workflows with upload allowlists, size limits, generated storage keys, malware scanning, private storage outside web root, and authorized downloads. Do not accept executable or active content.
4. Finish grade entry with teacher-allocation checks, marks <= exam maximum, immutable audit history, grading configuration, and reviewed report-card/PDF templates.
5. Finish fee payments as transactions with idempotent provider references, currency matching, balance locking, receipt issuance, refunds/voids, and reconciliation. Never update invoice `paid_amount` without a corresponding payment record and an audited transaction.
6. Extend the included parent dashboard with notification consent, custody/authorization workflows, and age-appropriate/student privacy safeguards.
7. Add automated tests for auth lockout, CSRF, permissions, tenant isolation, concurrent invoice/payment updates, and every destructive/personal-data operation. Add monitoring, security headers/CSP, log redaction, dependency scanning, and incident-response ownership.
8. Use a compiled CSS/JS build pipeline, reviewed accessibility checks, localization, and configured time zones. Display monetary values with currency-aware formatting.

## Policy documents

- [Acceptable Use Policy](docs/policies/acceptable-use.md)
- [Data Privacy and Retention Policy](docs/policies/data-privacy-retention.md)
- [Backup and Disaster Recovery Policy](docs/policies/backup-disaster-recovery.md)

These are operational templates, not legal advice. School counsel/privacy officers must tailor them to jurisdiction, contracts, student age, and applicable laws before adoption. FERPA/GDPR applicability and compliance depend on actual practices, contracts, access controls, retention schedules, and local law—not on this software alone.
