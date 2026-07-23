# Queue, Storage, Deployment, dan Reliability

> Bagian dari [PRD: Project Manager Laravel](../prd-laravel.md).

## 14. Queue dan Jobs

Jobs MVP/P1:

- `SendNotificationJob`
- `DispatchWebhookDeliveryJob`
- `RetryFailedWebhookDeliveryJob`
- `GenerateProjectReportExportJob`
- `CleanupExpiredExportsJob`
- `CleanupExpiredRefreshTokensJob`
- `SendOverdueTaskReminderJob`

Acceptance criteria:

- Job idempotent jika memungkinkan.
- Job menyimpan failure reason.
- Job tidak menyimpan secret mentah di payload.
- Queue worker berjalan sebagai Render Background Worker.
- Failed jobs dapat di-retry.

## 15. Service Providers dan Observers

### 15.1 Service Providers

- Bind `StorageUrlService`.
- Bind `WebhookSigner`.
- Bind `ActivityLogger`.
- Bind `TokenService`.
- Register queue routing bila queue dipisah.

### 15.2 Observers

- `TaskObserver`: activity log untuk status, priority, due date, assignee.
- `ProjectObserver`: activity log untuk status dan owner changes.
- `CommentObserver`: activity log dan mention extraction.
- `AttachmentObserver`: cleanup storage saat delete.
- `TeamMemberObserver`: audit role/member changes.

## 16. Database Requirements

Database utama menggunakan Supabase PostgreSQL.

Requirements:

- Semua migration berada di repository Laravel.
- Gunakan foreign key constraints.
- Gunakan unique constraints untuk email, team membership, project-team assignment, dan task additional assignment.
- Gunakan indexes untuk field filter umum:
  - `users.email`
  - `projects.team_id`
  - `projects.owner_id`
  - `projects.status`
  - `tasks.project_id`
  - `tasks.assignee_id`
  - `tasks.status`
  - `tasks.priority`
  - `tasks.due_date`
  - `notifications.recipient_id`
  - `activity_logs.entity_type, entity_id`
- Supabase connection menggunakan environment variable, lebih baik memakai pooler connection untuk deployment platform yang membuat banyak koneksi.
- Migration production dijalankan dari Render deploy/pre-deploy step.

## 17. Cloudflare R2 Storage Requirements

Cloudflare R2 dipakai sebagai object storage untuk avatar, attachment, dan export file.

Environment variables:

```env
FILESYSTEM_DISK=r2
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_BUCKET=
R2_ENDPOINT=https://<ACCOUNT_ID>.r2.cloudflarestorage.com
R2_PUBLIC_URL=
R2_USE_PATH_STYLE_ENDPOINT=true
```

Acceptance criteria:

- Upload attachment berhasil ke R2.
- Metadata tersimpan di database.
- Delete attachment menghapus object dari R2.
- URL file tidak membocorkan credential.
- Private file menggunakan signed/temporary URL atau endpoint proxy yang mengecek permission.

## 18. Render Deployment Requirements

Target Render services:

- Web Service: Laravel API.
- Background Worker: `php artisan queue:work`.
- Cron Job: scheduled commands.

Deployment requirements:

- Runtime boleh Docker agar konfigurasi PHP extension konsisten.
- Build menjalankan `composer install --no-dev`.
- Deploy/pre-deploy menjalankan:
  - `php artisan config:cache`
  - `php artisan route:cache`
  - `php artisan migrate --force`
- Start command menjalankan web server PHP yang sesuai untuk Render.
- Health check path: `/health`.
- Environment variables disimpan di Render dashboard atau Render Environment Group.
- Supabase database credential dan R2 credential tidak disimpan di repo.
- Production `APP_DEBUG=false`.

Example environment groups:

- App: `APP_KEY`, `APP_ENV`, `APP_URL`, `FRONTEND_URL`.
- Database: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DATABASE_URL`.
- Storage: `FILESYSTEM_DISK`, `R2_*`.
- Queue: `QUEUE_CONNECTION`.
- Mail/log: provider-specific optional values.

## 19. Observability dan Reliability

- `/health` memeriksa app, database, storage config, dan queue config secara ringan.
- Structured logs untuk request error, job failure, webhook delivery, dan auth failure.
- Correlation/request ID untuk tracing manual.
- Rate limit login, upload, webhook, dan export.
- Exception handler mengembalikan JSON konsisten.
- Failed job dan webhook delivery punya mekanisme retry.
- Query list memakai pagination.
- Eager loading digunakan untuk menghindari N+1.
- Dashboard count memakai optimized query atau cache jika diperlukan.
