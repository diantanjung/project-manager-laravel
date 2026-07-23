# Feature Requirements

> Bagian dari [PRD: Project Manager Laravel](../prd-laravel.md).

## 13. Feature Requirements

### 13.1 Authentication

Endpoint:

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/refresh`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`

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
