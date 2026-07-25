# Todo: Project Manager Laravel Portfolio

Sumber utama: [PRD modular](prd-laravel.md)

Dokumen ini hanya berisi roadmap high-level. Detail scope, domain model, endpoint, requirement fitur, deployment, dan testing ada di folder [prd-laravel](prd-laravel/).

Terakhir dicek: 25 Juli 2026. Test suite: `php artisan test --compact` lulus dengan 45 test dan 294 assertion.

## Phase 1 - Foundation

- [x] Setup Laravel API backend yang bisa berjalan dari fresh clone.
- [x] Konfigurasi database PostgreSQL, base API prefix, health check, dan response JSON standar.
- [x] Siapkan struktur aplikasi utama sesuai architecture PRD.
- [x] Siapkan enum, model user awal, authorization dasar, dan setup test Pest.

## Phase 2 - Authentication dan User

- [x] Implement authentication lifecycle: register, login, refresh, logout, dan current user.
- [x] Implement token/session management yang aman.
- [x] Implement user management untuk admin dan profile management untuk user.
- [x] Pastikan response user aman dan tidak membocorkan secret.

## Phase 3 - Core Project Management

- [x] Implement domain inti: team, member, project, project-team assignment, task, dan task assignment.
- [x] Implement CRUD dan list endpoint untuk team, project, dan task.
- [ ] Implement search, filter, sort, pagination, dan eager loading untuk query utama.
- [ ] Implement policies agar akses resource sesuai role dan scope project/team.

## Phase 4 - Collaboration

- [ ] Implement comment, mention, notification, attachment, dan activity log.
- [ ] Konfigurasi Cloudflare R2 untuk storage attachment/avatar/export.
- [ ] Pastikan file access private, tervalidasi, dan tidak membocorkan credential.
- [ ] Catat aktivitas penting untuk audit trail.

## Phase 5 - Portfolio Enhancements

- [x] Implement Kanban reorder yang atomik.
- [ ] Implement checklist/subtask dan task dependency/blocker.
- [x] Implement dashboard summary dan reporting dasar.
- [ ] Implement export CSV, saved filters, public share link, atau admin audit dashboard sesuai prioritas P1.

## Phase 6 - Integration dan Automation

- [ ] Implement outbound webhook management dan delivery log.
- [ ] Implement webhook signing, retry, dan queued delivery.
- [ ] Implement queue jobs untuk notification, webhook, export, cleanup, dan reminder.
- [ ] Implement scheduled commands untuk overdue reminder, digest, retry, cleanup, dan session cleanup.

## Phase 7 - Observability dan Reliability

- [ ] Implement observers/events untuk audit, notification, cleanup, dan side effect penting.
- [ ] Tambahkan structured logging, request/correlation ID, dan error JSON konsisten.
- [x] Tambahkan rate limit untuk endpoint sensitif.
- [ ] Pastikan job failure, retry, dan operational error mudah ditelusuri.

## Phase 8 - Demo dan Documentation

- [ ] Siapkan demo seeder dengan user, team, project, task, comment, notification, dan activity log realistis.
- [ ] Tulis README untuk setup local, environment variable, test, seed, deploy, dan demo credentials.
- [ ] Buat API documentation dan API collection.
- [ ] Tambahkan architecture diagram, screenshot, atau demo video singkat bila diperlukan untuk portfolio.

## Phase 9 - Deployment

- [ ] Deploy Laravel API ke Render Web Service.
- [ ] Deploy queue worker sebagai Render Background Worker.
- [ ] Deploy scheduler sebagai Render Cron Job.
- [ ] Hubungkan production/staging dengan Supabase PostgreSQL dan Cloudflare R2.
- [ ] Pastikan health check, migration, env vars, cache config, dan `APP_DEBUG=false` siap production.

## Testing High-Level

- [ ] Unit test domain logic penting: role, policy, token, mention, webhook signature, dependency, dan storage path.
- [x] Feature test flow utama: auth, user, team, project, task, comment, attachment, notification, activity, dashboard, export, dan webhook.
- [ ] Smoke test deployment: health check, migration, login demo user, R2 upload, queue worker, dan scheduler command.

## Final Acceptance

- [ ] API berjalan local dari fresh clone.
- [ ] Semua endpoint MVP tersedia dan terdokumentasi.
- [ ] RBAC dan resource-scoped access berjalan.
- [ ] Database migration, seed, queue, scheduler, R2 storage, dan Render deployment terbukti bekerja.
- [x] Test suite lulus.
- [ ] README membuat recruiter/interviewer bisa memahami value project dalam 5 menit.
