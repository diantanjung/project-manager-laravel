# Overview dan Scope

> Bagian dari [PRD: Project Manager Laravel](../prd-laravel.md).

## 1. Ringkasan

Project Manager Laravel adalah backend REST API untuk aplikasi manajemen proyek, tim, task, kolaborasi, attachment, notification, audit trail, dan operational automation. Project ini dibuat untuk membantu menyelesaikan management project agar team dapat mengorganize pekerjaan dengan baik dan project selesai tepat waktu.

Produk ini tidak bergantung pada repo backend lain. Repository baru harus dapat berdiri sendiri sebagai Laravel API backend, lengkap dengan migration, seeder, tests, API docs, deployment guide, dan environment configuration.

## 2. Tujuan Produk

1. Membantu team mengorganize pekerjaan, ownership, deadline, dan progress project secara terpusat.
2. Memastikan project manager dan stakeholder memiliki visibility yang jelas terhadap status delivery.
3. Menyediakan REST API yang aman, konsisten, terdokumentasi, dan mudah diintegrasikan dengan frontend.
4. Mendukung workflow task dari perencanaan, assignment, kolaborasi, review, sampai selesai.
5. Mengurangi risiko keterlambatan project melalui notification, audit trail, dashboard, dan reporting.
6. Menyediakan fondasi operasional yang siap dijalankan dengan queue, scheduler, storage, monitoring, testing, dan deployment.

## 3. Problem Statement

Tim produk sering mengelola pekerjaan melalui banyak tempat: chat, spreadsheet, issue tracker, dokumen, dan meeting notes. Akibatnya ownership task, progress, due date, attachment, dan keputusan teknis tidak selalu terlihat jelas.

Project Manager Laravel menyediakan satu backend API untuk mengelola proyek, tim, task, komentar, attachment, notifikasi, audit trail, dan automasi operasional agar tim dapat bekerja lebih terstruktur dan terukur.

## 4. Target Pengguna

| Persona | Kebutuhan utama |
| --- | --- |
| Admin | Mengelola user, role, dan konfigurasi global |
| Product Owner | Mengelola portfolio project, team, priority, dan delivery visibility |
| Project Manager | Membuat project, mengatur task, assign member, dan memantau progress |
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

### 5.2 P1 Product Enhancements

- Kanban reorder endpoint yang atomik.
- Task checklist/subtask.
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
