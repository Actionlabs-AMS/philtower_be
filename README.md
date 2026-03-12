# PhilTower Backend API

Laravel backend for **PhilTower** (PTCI ServiceDesk): support ticketing, SLA, user management, and system administration. Built on the BaseCode Laravel stack with Sanctum, 2FA, audit trail, backup/restore, and optional Microsoft Graph email.

---

## Features

### Support & Ticketing
- **Ticket requests** – Create, update, and manage support tickets
- **SLA** – Service level agreements and SLA clock tracking
- **Ticket statuses & service types** – Configurable workflow and categories
- **CSAT** – Customer satisfaction feedback
- **Ticket analytics & dashboard** – Reporting and metrics

### Security & Auth
- **Two-Factor Authentication (2FA)** – Email codes and backup codes
- **Laravel Sanctum** – API token authentication
- **Role-based access control (RBAC)** – Permissions and roles
- **Password security** – Salt, pepper, bcrypt
- **Audit trail** – Activity logging; configurable retention
- **Rate limiting** – Throttling on auth and API routes
- **Optional field encryption & GDPR anonymization** – Per-model configuration

### System & Admin
- **User management** – CRUD, bulk actions, soft deletes
- **Navigation** – Backend-driven menus and routes
- **Options** – System settings (email, security, etc.)
- **Backup & restore** – Database and/or files; scheduled or on-demand; optional encryption
- **Email** – Sendmail, SMTP, or Microsoft Graph (configurable via options + .env)
- **Swagger/OpenAPI** – Interactive API docs at `/api/documentation`

---

## Tech Stack

| Layer        | Technology |
|-------------|------------|
| Framework   | Laravel 10.x |
| PHP         | 8.1+ |
| Auth        | Laravel Sanctum |
| Database    | MySQL 5.7+ |
| API docs    | L5-Swagger (OpenAPI 3.0) |
| Email       | SMTP / sendmail / Microsoft Graph |
| Optional    | Intervention Image, Guzzle, cron-expression |

---

## Requirements

- PHP 8.1+
- Composer
- MySQL 5.7+
- (Optional) Microsoft Azure app for Graph-based email

---

## Installation

```bash
git clone <repository-url>
cd philtower_be
composer install
cp .env.example .env
php artisan key:generate
```

Configure `.env` (database, mail, optional Microsoft Graph). Then:

```bash
php artisan migrate --seed
php artisan l5-swagger:generate
php artisan serve
```

- **API base:** `http://127.0.0.1:8000`  
- **Swagger UI:** `http://127.0.0.1:8000/api/documentation`

---

## Environment (highlights)

```env
APP_NAME="PhilTower"
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=philtower
DB_USERNAME=root
DB_PASSWORD=

# Mail: sendmail, smtp, or log (options UI can override; on Windows, sendmail falls back to SMTP if configured)
MAIL_MAILER=sendmail
MAIL_FROM_ADDRESS=support@servicedesk.com.ph
MAIL_FROM_NAME="PTCI ServiceDesk"

# Optional: Microsoft Graph (when mailer is "microsoft" in options)
MICROSOFT_TENANT_ID=
MICROSOFT_CLIENT_ID=
MICROSOFT_CLIENT_SECRET=
MICROSOFT_SENDER_EMAIL=
```

See `.env.example` and the **Configuration** section below for full options (security, CORS, backup, audit, encryption, anonymization).

---

## Test email

```bash
# Send test email (uses .env mail settings for sendmail type)
php artisan test:email bautistael23@gmail.com --type=sendmail

# Other types: simple (Microsoft Graph), 2fa (with --user-id=1), config (validate only)
php artisan test:email user@example.com --type=simple
```

API test endpoint: `POST /api/test-smtp-email` (body: `email` optional, defaults to configured test address).

---

## Configuration

- **Mail** – Default from `config/mail.php` and `.env`; overridden at runtime from **Options** (System Settings) when the `options` table exists. On Windows, if “sendmail” is selected but no sendmail binary is configured, the app falls back to SMTP when credentials exist.
- **CORS** – `config/cors.php` (e.g. `CORS_ALLOWED_ORIGINS`).
- **Backup** – `BACKUP_*` in `.env`; scheduling via UI or webhook/cron.
- **Audit trail** – `AUDIT_TRAIL_*` in `.env`.
- **Encryption / anonymization** – `config/encryption.php`, `config/anonymization.php`, and `ENCRYPTION_*` / `ANONYMIZATION_*` / `GDPR_*` in `.env`.

---

## Testing

```bash
php artisan test
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

---

## Project structure (high level)

```
philtower_be/
├── app/
│   ├── Console/Commands/     # test:email, backup, encrypt, anonymize, etc.
│   ├── Http/Controllers/Api/
│   │   ├── Support/          # TicketRequest, Sla, Csat, ServiceType, etc.
│   │   └── ...
│   ├── Models/
│   │   ├── Support/          # TicketRequest, Sla, TicketStatus, etc.
│   │   └── ...
│   ├── Services/
│   │   ├── Support/          # TicketRequestService, SlaService, etc.
│   │   └── OptionService.php # Email/system options
│   └── ...
├── config/
├── database/migrations/
├── routes/api.php
└── .env
```

---

## API documentation

- **Swagger UI:** `http://127.0.0.1:8000/api/documentation`
- Regenerate after changing annotations: `php artisan l5-swagger:generate`

Documented areas include auth, 2FA, user/role management, support (ticket requests, SLA, CSAT, service types), backups, audit trail, security, and system settings.

---

## Frontend

The **PhilTower frontend** (`philtower_fe`) is a separate React + Vite + Chakra UI app that consumes this API. Set `ADMIN_APP_URL` (and CORS) to the frontend origin.

---

## Further documentation

This codebase extends the **BaseCode** Laravel template. For detailed coverage of the following, see the rest of this README and the codebase:

- Backup & restore (scheduling, encryption, webhook, retention)
- Audit trail (log format, endpoints, retention)
- Database encryption and GDPR anonymization (traits, config, artisan commands)
- Microsoft Graph setup and usage
- Security (CORS, CSP, headers, rate limits)
- Deployment checklist and production security

---

## Changelog

- **2025** – Support module (ticket requests, SLA, CSAT, service types); email test command; Windows sendmail fallback to SMTP when configured.
- **v2.3** – Backup/restore, session timeout integration, remember-me.
- **v2.2** – Database encryption, GDPR anonymization, CORS/CSP adjustments.
- **v2.0** – Audit trail, Swagger/OpenAPI, 2FA, security hardening.

See Git history and in-README sections for full details.
