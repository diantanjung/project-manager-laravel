# Testing, Demo Data, Milestones, dan DoD

> Bagian dari [PRD: Project Manager Laravel](../prd-laravel.md).

## 21. Testing Plan

### Unit Tests

- Role hierarchy.
- Policy decisions.
- Mention parser.
- Webhook signature generator/verifier.
- Token hashing/expiry.
- Storage path generator.

### Feature Tests

- Register/login/refresh/logout.
- Admin user CRUD.
- Team membership duplicate conflict.
- Project CRUD and visibility scope.
- Task CRUD and visibility scope.
- Task reorder transaction.
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

### Milestone 4 - Advanced Product Features

- Checklist.
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

### Milestone 6 - Product Polish

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

Project siap digunakan oleh team jika:

1. Project manager, team member, dan stakeholder dapat membaca README dan memahami value project dalam 5 menit.
2. Developer dapat menjalankan local setup tanpa menebak konfigurasi.
3. Demo API dapat diakses dari Render staging URL.
4. Supabase database berisi demo seed.
5. Cloudflare R2 upload terbukti bekerja.
6. Queue worker memproses notification/webhook/export job.
7. Scheduled command dapat berjalan via Render Cron Job.
8. Test suite lulus.
9. API docs mencakup auth, user, team, project, task, comment, attachment, notification, dashboard, export, dan webhook.
10. Project mendukung ownership end-to-end: product planning, task execution, collaboration, reporting, security, performance, deployment, monitoring, dan testing.
