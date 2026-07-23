# Fondasi Teknis dan Architecture

> Bagian dari [PRD: Project Manager Laravel](../prd-laravel.md).

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
  Enums/
  Http/
    Controllers/Api/V1/
    Middleware/
    Requests/
    Resources/
  Jobs/
  Models/
  Observers/
  Policies/
  Providers/
  Services/
    Auth/
    Export/
    Integrations/
    Storage/
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
- Services menangani business logic, integration boundary, dan proses yang dipakai ulang.
- Eloquent Models menangani relation, casts, scopes, dan domain helper sederhana.
- Policies menangani resource authorization.
- Observers menangani efek samping model seperti audit log, notifikasi, dan cleanup storage.
- Jobs menangani proses asynchronous.
- API Resources menjaga response JSON konsisten dan camelCase.
