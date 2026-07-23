# PRD: Project Manager Laravel

> Status: Product Requirements Document untuk repo baru `project-manager-laravel`  
> Versi: 2.0  
> Tanggal: 21 Juli 2026  
> Target role: Backend Developer PHP/Laravel  
> Target deploy: Render  
> Database: Supabase PostgreSQL  
> Object storage: Cloudflare R2  
> Frontend target: React/TypeScript app terpisah

## Daftar Isi Modular

PRD ini dipecah menjadi beberapa dokumen kecil agar tiap area bisa dibaca, direview, dan diimplementasikan dengan lebih fokus.

| Dokumen | Isi |
| --- | --- |
| [Overview dan Scope](prd-laravel/01-overview.md) | Section 1-6 |
| [Fondasi Teknis dan Architecture](prd-laravel/02-technical-foundation.md) | Section 7-8 |
| [API Design, Auth, dan Permissions](prd-laravel/03-api-auth-permissions.md) | Section 9-10 |
| [Core Domain Model dan Enum](prd-laravel/04-domain-model.md) | Section 11-12 |
| [Feature Requirements](prd-laravel/05-feature-requirements.md) | Section 13 |
| [Queue, Storage, Deployment, dan Reliability](prd-laravel/06-operations-deployment.md) | Section 14-19 |
| [API Endpoint MVP](prd-laravel/07-api-endpoints.md) | Section 20 |
| [Testing, Demo Data, Milestones, dan DoD](prd-laravel/08-delivery-plan.md) | Section 21-25 |

## Cara Pakai

- Mulai dari [Overview dan Scope](prd-laravel/01-overview.md) untuk memahami produk dan batasannya.
- Gunakan [Feature Requirements](prd-laravel/05-feature-requirements.md) sebagai acuan utama saat implementasi fitur.
- Gunakan [API Endpoint MVP](prd-laravel/07-api-endpoints.md) saat membuat route, controller, request, resource, dan test feature.
- Gunakan [Testing, Demo Data, Milestones, dan DoD](prd-laravel/08-delivery-plan.md) untuk tracking progres sampai siap digunakan oleh tim.
