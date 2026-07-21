# PRD: Project Manager Laravel Portfolio

> Status: Product Requirements Document untuk repo baru `project-manager-laravel`  
> Versi: 2.0  
> Tanggal: 21 Juli 2026  
> Target role: Backend Developer PHP/Laravel  
> Target deploy: Render  
> Database: Supabase PostgreSQL  
> Object storage: Cloudflare R2  
> Frontend target: React/TypeScript app terpisah

## 1. Ringkasan

Project Manager Laravel adalah backend REST API untuk aplikasi manajemen proyek, tim, task, kolaborasi, attachment, notification, audit trail, dan operational automation. Project ini dibuat sebagai portfolio backend Laravel yang menunjukkan kemampuan membangun aplikasi product-ready: API design, authentication, RBAC, database design, queue/job processing, integration, file storage, observability, testing, dan deployment.

Produk ini tidak bergantung pada repo backend lain. Repository baru harus dapat berdiri sendiri sebagai Laravel API backend, lengkap dengan migration, seeder, tests, API docs, deployment guide, dan environment configuration.

## 2. Tujuan Portfolio

1. Menunjukkan kemampuan hands-on PHP/Laravel minimal setara aplikasi produksi kecil-menengah.
2. Menunjukkan kemampuan membangun REST API yang aman, konsisten, terdokumentasi, dan testable.
3. Menunjukkan pemahaman Laravel design patterns: Form Request, Eloquent, Policies, Observers, Events, Jobs, Queues, Service Providers, Resources, dan Scheduler.
4. Menunjukkan kemampuan SQL dan database modeling menggunakan PostgreSQL.
5. Menunjukkan kemampuan deployment dan operasional menggunakan Render, Supabase PostgreSQL, Cloudflare R2, queue worker, scheduler, health check, dan structured logging.
6. Menunjukkan kemampuan bekerja mandiri dari PRD sampai implementasi, testing, dan deployment.
7. Menyediakan demo yang mudah dijalankan oleh recruiter/interviewer.

## 3. Problem Statement

Tim produk sering mengelola pekerjaan melalui banyak tempat: chat, spreadsheet, issue tracker, dokumen, dan meeting notes. Akibatnya ownership task, progress, due date, blocker, attachment, dan keputusan teknis tidak selalu terlihat jelas.

Project Manager Laravel menyediakan satu backend API untuk mengelola proyek, tim, task, komentar, attachment, notifikasi, audit trail, dan automasi operasional agar tim dapat bekerja lebih terstruktur dan terukur.

## 4. Target Pengguna

| Persona | Kebutuhan utama |
| --- | --- |
| Admin | Mengelola user, role, dan konfigurasi global |
| Product Owner | Mengelola portfolio project, team, priority, dan delivery visibility |
| Project Manager | Membuat project, mengatur task, assign member, memantau progress, dan mengelola blocker |
| Team Member | Melihat task, update status, komentar, upload attachment, dan menerima notifikasi |
| External Stakeholder | Melihat project summary terbatas dan export/report yang dibagikan |

## 5. Scope Produk

### 5.1 MVP

- Authentication dan session management.
- User management.
- Team management dan team membership.
- Project management.
- Project-team assignment.
- Task management dengan Kanban workflow.
- Primary assignee dan additional assignees.
- Comment dan mention.
- Attachment upload ke Cloudflare R2.
- Notification system.
- Activity log/audit trail.
- Dashboard summary dasar.
- Search, filter, sort, dan pagination.
- API documentation.
- Test suite.
- Deployment ke Render menggunakan Supabase PostgreSQL dan Cloudflare R2.

### 5.2 P1 Portfolio Enhancements

- Kanban reorder endpoint yang atomik.
- Task checklist/subtask.
- Task dependency/blocker.
- Saved filters.
- Notification digest via scheduled job.
- Overdue task reminder.
- Webhook outbound untuk integration demo.
- Import/export CSV.
- Public share link untuk project report.
- Admin audit dashboard.

### 5.3 P2 Nice to Have

- OAuth login Google/GitHub.
- Slack/Discord webhook integration.
- AI project summary.
- Real-time notification menggunakan Pusher/Soketi/SSE.
- Advanced analytics: cycle time, throughput, workload per member.
- Multi-tenant organization model.

## 6. Non-Goals

- Tidak membangun frontend Laravel Blade sebagai UI utama.
- Tidak membangun native mobile app.
- Tidak membangun billing/subscription.
- Tidak membuat enterprise-grade multi-region deployment.
- Tidak memakai cloud provider lain sebagai target utama selain Render, Supabase, dan Cloudflare R2.

## 7. Tech Stack

- Language: PHP 8.3+.
- Framework: Laravel 12/13.
- Database: Supabase PostgreSQL.
- ORM: Eloquent.
- Auth: Laravel Sanctum atau custom token service dengan refresh token cookie.
- Validation: Form Request.
- Authorization: Policies, Gates, dan role middleware.
- Queue: database queue untuk MVP; Redis-compatible queue dapat menjadi improvement.
- Scheduler: Laravel Scheduler, dijalankan melalui Render Cron Job.
- Object storage: Cloudflare R2 via Laravel S3-compatible filesystem disk.
- API docs: OpenAPI/Swagger atau Scribe.
- Tests: Pest atau PHPUnit.
- Local dev: Docker Compose untuk PHP, PostgreSQL local optional, queue worker, dan mail/log sink.
- Deployment: Render Web Service, Render Background Worker, Render Cron Job.

Catatan implementasi:

- Laravel dapat digunakan sebagai API backend untuk frontend JavaScript/mobile dan menyediakan routing, validation, caching, queues, file storage, notifications, dan testing.
- Laravel filesystem mendukung S3-compatible storage termasuk Cloudflare R2 melalui konfigurasi endpoint dan credential.
- Supabase menyediakan hosted PostgreSQL dan connection pooling; aplikasi Laravel dapat memakai Supabase sebagai database PostgreSQL eksternal.
- Render mendukung Web Service untuk API, Background Worker untuk queue, dan Cron Job untuk scheduled command.

## 8. Architecture

```text
app/
  Actions/
    Auth/
    Projects/
    Tasks/
    Notifications/
    Reports/
  DTO/
  Enums/
  Events/
  Exceptions/
  Http/
    Controllers/Api/V1/
    Middleware/
    Requests/
    Resources/
  Jobs/
  Listeners/
  Models/
  Observers/
  Policies/
  Providers/
  Services/
    Activity/
    Auth/
    Export/
    Integrations/
    Storage/
  Support/
database/
  factories/
  migrations/
  seeders/
routes/
  api.php
  console.php
tests/
  Feature/
  Unit/
```

### 8.1 Layering

- Controllers menangani HTTP orchestration.
- Form Requests menangani validasi dan request-level authorization.
- Actions/Services menangani business logic dan transaction boundary.
- Eloquent Models menangani relation, casts, scopes, dan domain helper sederhana.
- Policies menangani resource authorization.
- Observers/Events menangani efek samping seperti audit log dan notifikasi.
- Jobs menangani proses asynchronous.
- API Resources menjaga response JSON konsisten dan camelCase.

## 9. API Design

### 9.1 Prefix dan Format

- Prefix utama: `/api/v1`.
- Request/response: JSON.
- Upload file: `multipart/form-data`.
- Authenticated endpoint menggunakan:

```http
Authorization: Bearer <accessToken>
```

### 9.2 Response Envelope

Single resource:

```json
{
  "data": {}
}
```

Paginated list:

```json
{
  "data": [],
  "pagination": {
    "page": 1,
    "limit": 20,
    "totalItems": 0,
    "totalPages": 0
  }
}
```

Error:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### 9.3 Field Naming

- Database menggunakan snake_case.
- API response menggunakan camelCase.
- Timestamp menggunakan ISO 8601.
- Enum value menggunakan string stabil dan terdokumentasi.

## 10. Roles dan Permissions

Role hierarchy:

```text
teamMember < projectManager < productOwner < admin
```

| Capability | Team Member | Project Manager | Product Owner | Admin |
| --- | :---: | :---: | :---: | :---: |
| View assigned projects/tasks | Yes | Yes | Yes | Yes |
| Create comment | Yes | Yes | Yes | Yes |
| Upload attachment | Yes | Yes | Yes | Yes |
| Update own task status | Yes | Yes | Yes | Yes |
| Create task | No | Yes | Yes | Yes |
| Assign task | No | Yes | Yes | Yes |
| Create project | No | Yes | Yes | Yes |
| Manage team members | No | Yes | Yes | Yes |
| Create team | No | No | Yes | Yes |
| Manage users | No | No | No | Yes |
| View audit log | No | Project scope | Product scope | Global |
| Manage system settings | No | No | No | Yes |

## 11. Core Domain Model

### 11.1 User

- `id`
- `name`
- `email`
- `password`
- `avatar_path`
- `role`
- `timezone`
- `last_login_at`
- `created_at`
- `updated_at`

### 11.2 RefreshToken

- `id`
- `user_id`
- `token_hash`
- `expires_at`
- `revoked_at`
- `created_at`

### 11.3 Team

- `id`
- `name`
- `description`
- `created_at`
- `updated_at`

### 11.4 TeamMember

- `id`
- `team_id`
- `user_id`
- `role`
- `joined_at`

### 11.5 Project

- `id`
- `name`
- `description`
- `team_id`
- `owner_id`
- `status`
- `start_date`
- `due_date`
- `created_at`
- `updated_at`

### 11.6 ProjectTeam

- `id`
- `project_id`
- `team_id`
- `created_at`

### 11.7 Task

- `id`
- `title`
- `description`
- `status`
- `priority`
- `project_id`
- `creator_id`
- `assignee_id`
- `due_date`
- `position`
- `estimate_minutes`
- `completed_at`
- `created_at`
- `updated_at`

### 11.8 TaskAssignment

- `id`
- `task_id`
- `user_id`
- `assigned_by`
- `created_at`

### 11.9 Comment

- `id`
- `task_id`
- `author_id`
- `content`
- `created_at`
- `updated_at`

### 11.10 Attachment

- `id`
- `task_id`
- `uploader_id`
- `disk`
- `path`
- `original_name`
- `mime_type`
- `size`
- `created_at`

### 11.11 Notification

- `id`
- `recipient_id`
- `actor_id`
- `task_id`
- `type`
- `title`
- `message`
- `read_at`
- `created_at`

### 11.12 ActivityLog

- `id`
- `actor_id`
- `entity_type`
- `entity_id`
- `action`
- `before`
- `after`
- `ip_address`
- `user_agent`
- `created_at`

### 11.13 WebhookEndpoint

- `id`
- `name`
- `url`
- `secret`
- `events`
- `is_active`
- `created_at`
- `updated_at`

### 11.14 WebhookDelivery

- `id`
- `webhook_endpoint_id`
- `event_type`
- `payload`
- `status`
- `attempt_count`
- `last_error`
- `delivered_at`
- `created_at`
- `updated_at`

## 12. Enum

```text
UserRole         = admin | productOwner | projectManager | teamMember
TeamMemberRole   = owner | admin | member
ProjectStatus    = planning | active | paused | completed | archived
TaskStatus       = backlog | todo | in_progress | review | done
TaskPriority     = low | medium | high | urgent
NotificationType = task_assigned | mention | task_due | project_update | system_alert
WebhookEvent     = task.created | task.updated | task.completed | comment.created | project.updated
```

## 13. Feature Requirements

### 13.1 Authentication

Endpoint:

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/refresh`
- `POST /api/v1/auth/logout`
- `GET /api/v1/me`

Requirements:

- Register public membuat role default `teamMember`.
- Login menghasilkan access token.
- Refresh token disimpan di cookie `HttpOnly`, di-hash di database, dan dirotasi saat refresh.
- Logout mencabut refresh token aktif.
- Login endpoint memiliki rate limiting.

### 13.2 User Management

- Admin dapat membuat, membaca, mengubah, dan menghapus user.
- User dapat memperbarui profil sendiri.
- Upload avatar menggunakan Cloudflare R2.
- Avatar URL dapat berupa temporary signed URL atau public CDN URL sesuai konfigurasi.
- Response user tidak pernah memuat password, refresh token, atau secret.

### 13.3 Team Management

- Product Owner dan Admin dapat membuat/mengubah team.
- Admin dapat menghapus team.
- Project Manager dapat mengelola member pada team yang ia kelola.
- Duplicate membership harus menghasilkan `409 Conflict`.

### 13.4 Project Management

- Project memiliki primary team dan owner.
- Project dapat memiliki additional teams.
- Project list mendukung search, filter status/team/owner, sort, dan pagination.
- Project detail menampilkan summary task count per status.
- User di luar scope project tidak dapat membaca detail project.

### 13.5 Task Management

- Task memiliki status Kanban: `backlog`, `todo`, `in_progress`, `review`, `done`.
- Task memiliki priority, due date, position, estimate, creator, primary assignee, dan additional assignees.
- Task list mendukung filter project/status/priority/assignee/due date/search.
- Task detail menampilkan komentar, attachment, assignments, dan activity.
- Project Manager dapat reorder task secara atomik.
- Task update menghasilkan activity log.

### 13.6 Checklist dan Dependency

- Task dapat memiliki checklist item.
- Checklist item memiliki title, checked state, dan position.
- Task dapat diblokir oleh task lain.
- Task tidak boleh `done` jika dependency wajib belum selesai.
- Project summary menampilkan jumlah blocked task.

### 13.7 Comment dan Mention

- User dengan akses task dapat membuat komentar.
- Author dapat update/delete komentarnya sendiri.
- Mention menggunakan format `@name` untuk MVP.
- Mention menghasilkan notification `mention`.
- Comment create/update menghasilkan activity log.

### 13.8 Attachment dan Cloudflare R2

- Attachment upload menggunakan Laravel filesystem disk S3-compatible yang diarahkan ke Cloudflare R2.
- Validasi file berdasarkan MIME type dan ukuran.
- Attachment metadata tersimpan di database.
- Download hanya diizinkan untuk user yang memiliki akses task.
- Delete attachment menghapus metadata dan object storage.
- Supported file MVP: image, PDF, text, zip dengan size limit configurable.

### 13.9 Notification

- Notification dibuat untuk assignment, mention, overdue reminder, dan project update.
- User dapat melihat notification miliknya.
- User dapat mark one atau mark all as read.
- Notification digest dapat dikirim lewat scheduled job.

### 13.10 Activity Log

- Sistem mencatat perubahan penting:
  - project create/update/archive;
  - task create/update/status change/reorder;
  - assignee change;
  - comment create/update/delete;
  - attachment upload/delete;
  - role/member change.
- Activity log menyimpan actor, entity, action, before, after, IP, user agent, timestamp.
- Project Manager dapat melihat activity di project scope.
- Admin dapat melihat semua activity.

### 13.11 Dashboard dan Reporting

- Dashboard API menyediakan:
  - total project active;
  - task count per status;
  - overdue task count;
  - workload per member;
  - recently updated tasks;
  - blocked tasks.
- Export CSV untuk project report dan task list.
- Export dijalankan via job jika data besar.

### 13.12 Integration Demo

- Admin dapat membuat webhook endpoint.
- Event tertentu mengirim outbound webhook via queue job.
- Webhook delivery memiliki retry dengan backoff.
- Delivery log dapat dilihat admin.
- Payload ditandatangani menggunakan HMAC secret.

### 13.13 Operational Automation

Scheduled jobs:

- `tasks:send-overdue-reminders` setiap hari.
- `notifications:send-digest` setiap hari kerja.
- `webhooks:retry-failed` setiap 15 menit.
- `exports:cleanup-expired` setiap hari.
- `sessions:cleanup-expired-refresh-tokens` setiap hari.

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

## 20. API Endpoint MVP

### Auth

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/refresh`
- `POST /api/v1/auth/logout`
- `GET /api/v1/me`

### Users

- `GET /api/v1/users`
- `POST /api/v1/users`
- `GET /api/v1/users/{user}`
- `PATCH /api/v1/users/{user}`
- `DELETE /api/v1/users/{user}`
- `POST /api/v1/users/{user}/avatar`
- `GET /api/v1/users/{user}/tasks`

### Teams

- `GET /api/v1/teams`
- `POST /api/v1/teams`
- `GET /api/v1/teams/{team}`
- `PATCH /api/v1/teams/{team}`
- `DELETE /api/v1/teams/{team}`
- `GET /api/v1/teams/{team}/members`
- `POST /api/v1/teams/{team}/members`
- `DELETE /api/v1/teams/{team}/members/{user}`

### Projects

- `GET /api/v1/projects`
- `POST /api/v1/projects`
- `GET /api/v1/projects/{project}`
- `PATCH /api/v1/projects/{project}`
- `DELETE /api/v1/projects/{project}`
- `GET /api/v1/projects/{project}/tasks`
- `GET /api/v1/projects/{project}/activity`
- `GET /api/v1/projects/{project}/summary`
- `POST /api/v1/projects/{project}/teams`
- `DELETE /api/v1/projects/{project}/teams/{team}`

### Tasks

- `GET /api/v1/tasks`
- `POST /api/v1/tasks`
- `GET /api/v1/tasks/{task}`
- `PATCH /api/v1/tasks/{task}`
- `DELETE /api/v1/tasks/{task}`
- `PATCH /api/v1/tasks/{task}/status`
- `POST /api/v1/tasks/reorder`
- `GET /api/v1/tasks/{task}/comments`
- `GET /api/v1/tasks/{task}/attachments`
- `GET /api/v1/tasks/{task}/activity`
- `POST /api/v1/tasks/{task}/assignments`
- `DELETE /api/v1/tasks/{task}/assignments/{user}`
- `POST /api/v1/tasks/{task}/dependencies`
- `DELETE /api/v1/tasks/{task}/dependencies/{dependency}`

### Checklists

- `POST /api/v1/tasks/{task}/checklist-items`
- `PATCH /api/v1/checklist-items/{item}`
- `DELETE /api/v1/checklist-items/{item}`

### Comments

- `POST /api/v1/tasks/{task}/comments`
- `PATCH /api/v1/comments/{comment}`
- `DELETE /api/v1/comments/{comment}`

### Attachments

- `POST /api/v1/tasks/{task}/attachments`
- `GET /api/v1/attachments/{attachment}/download`
- `DELETE /api/v1/attachments/{attachment}`

### Notifications

- `GET /api/v1/notifications`
- `PATCH /api/v1/notifications/{notification}/read`
- `PATCH /api/v1/notifications/read-all`

### Dashboard, Export, Webhook

- `GET /api/v1/dashboard`
- `POST /api/v1/exports/project-report`
- `GET /api/v1/exports/{export}`
- `GET /api/v1/activity-logs`
- `GET /api/v1/webhook-endpoints`
- `POST /api/v1/webhook-endpoints`
- `PATCH /api/v1/webhook-endpoints/{webhookEndpoint}`
- `DELETE /api/v1/webhook-endpoints/{webhookEndpoint}`
- `GET /api/v1/webhook-deliveries`

## 21. Testing Plan

### Unit Tests

- Role hierarchy.
- Policy decisions.
- Mention parser.
- Webhook signature generator/verifier.
- Token hashing/expiry.
- Task dependency validation.
- Storage path generator.

### Feature Tests

- Register/login/refresh/logout.
- Admin user CRUD.
- Team membership duplicate conflict.
- Project CRUD and visibility scope.
- Task CRUD and visibility scope.
- Task reorder transaction.
- Task dependency blocks completion.
- Comment mention creates notification.
- Attachment upload/delete using fake storage.
- Notification mark read.
- Activity log generated for important changes.
- Webhook delivery queued and signed.
- Dashboard summary returns expected counts.

### Deployment/Smoke Tests

- `/health` returns OK.
- Migration runs on empty Supabase database.
- Seeded demo user can login.
- R2 upload works in staging.
- Queue worker processes notification job.
- Cron job command can run manually.

## 22. Demo Data

Seeder harus membuat:

- 1 admin.
- 1 product owner.
- 2 project managers.
- 6 team members.
- 3 teams.
- 4 projects.
- 30 tasks dengan variasi status dan priority.
- Comments, mentions, attachments dummy metadata.
- Notifications read/unread.
- Activity logs.

Demo credentials harus ditulis di README hanya untuk local/staging demo, bukan production.

## 23. Milestones

### Milestone 1 - Foundation

- Laravel project setup.
- Auth.
- User model.
- Role middleware.
- Health check.
- Supabase connection.
- Base test setup.

### Milestone 2 - Core Project Management

- Team, member, project, task.
- Policies.
- Pagination/filter/sort.
- Seed data.
- Feature tests.

### Milestone 3 - Collaboration

- Comment.
- Mention.
- Notification.
- Attachment upload to R2.
- Activity log.

### Milestone 4 - Advanced Portfolio Features

- Checklist.
- Dependency/blocker.
- Kanban reorder.
- Dashboard.
- Export CSV.

### Milestone 5 - Integration and Operations

- Webhook outbound.
- Queue jobs.
- Scheduler commands.
- Render background worker.
- Render cron job.
- Deployment documentation.

### Milestone 6 - Portfolio Polish

- API docs.
- README.
- Architecture diagram.
- Postman collection.
- Screenshots or short demo video.
- Final smoke test on Render staging URL.

## 24. Acceptance Criteria

- API dapat dijalankan local dari fresh clone.
- Database dapat dibuat via Laravel migrations.
- Backend dapat terhubung ke Supabase PostgreSQL.
- Attachment/avatar/export dapat tersimpan ke Cloudflare R2.
- Render Web Service, Background Worker, dan Cron Job terdokumentasi.
- Semua endpoint MVP tersedia.
- RBAC dan resource-scoped access berjalan.
- Queue jobs dan scheduler berjalan.
- Test suite lulus.
- README menjelaskan setup, env, test, seed, deploy, dan demo credentials.
- API docs tersedia dan bisa dipakai frontend developer.

## 25. Definition of Done

Project siap dipakai sebagai portfolio jika:

1. Recruiter/interviewer dapat membaca README dan memahami value project dalam 5 menit.
2. Developer dapat menjalankan local setup tanpa menebak konfigurasi.
3. Demo API dapat diakses dari Render staging URL.
4. Supabase database berisi demo seed.
5. Cloudflare R2 upload terbukti bekerja.
6. Queue worker memproses notification/webhook/export job.
7. Scheduled command dapat berjalan via Render Cron Job.
8. Test suite lulus.
9. API docs mencakup auth, user, team, project, task, comment, attachment, notification, dashboard, export, dan webhook.
10. Project menunjukkan ownership end-to-end: product thinking, backend architecture, data model, security, performance, deployment, monitoring, dan testing.
