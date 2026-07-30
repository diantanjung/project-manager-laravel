# Project Manager Laravel

Project Manager Laravel is a REST API backend for managing projects, teams, tasks, collaboration, attachments, notifications, audit trails, dashboards, reporting, and webhooks.

It is built to help teams organize work in one place: who owns what, how far each task has progressed, which deadlines need attention, and which important activities happened inside a project.

## What Is This Project For?

This project can be used as the backend for project management applications such as Kanban boards, delivery dashboards, task trackers, or internal productivity tools.

Primary users:

- Admins who manage users, roles, and global configuration.
- Product Owners who monitor project portfolios and delivery visibility.
- Project Managers who create projects, manage tasks, assign members, and track progress.
- Team Members who update tasks, comment, upload attachments, and receive notifications.
- Stakeholders who view limited summaries or reports.

## Main Features

- Authentication with access tokens and refresh tokens.
- User management and role-based access control.
- Team management and membership.
- Project management with owners, primary teams, and additional teams.
- Task management with a Kanban workflow: `backlog`, `todo`, `in_progress`, `review`, `done`.
- Primary assignees and additional assignees.
- Task checklist items.
- Comments and mentions.
- Attachment uploads using an S3-compatible filesystem, including Cloudflare R2.
- Notifications for assignments, mentions, overdue reminders, and project updates.
- Activity log/audit trail for important changes.
- Dashboard summaries: active projects, task status, workload, due soon, overdue, and latest updates.
- Project report exports.
- Outbound webhook endpoints and delivery logs.
- Test suite powered by Pest.

## Tech Stack

- Laravel 13
- PHP 8.3+
- Inertia Laravel 3
- React 19
- Tailwind CSS 4
- Vite
- Pest 4
- Larastan
- Laravel Pint
- Default local database: SQLite
- Production-oriented storage: Cloudflare R2 / S3-compatible filesystem

## Project Structure

```text
app/Http/Controllers/Api/V1   API controllers
app/Http/Requests/Api/V1      Form request validation
app/Http/Resources/Api/V1     API resources
app/Models                    Eloquent models
app/Policies                  Authorization policies
database/migrations           Database schema
database/seeders              Seeders
routes/api.php                API routes
tests/Feature                 Feature tests
doc/prd-laravel               Product and technical documentation
```

## Local Setup

Make sure PHP, Composer, Node.js, and npm are installed.

1. Install PHP dependencies:

```bash
composer install
```

2. Install frontend dependencies:

```bash
npm install
```

3. Create the environment file:

```bash
cp .env.example .env
php artisan key:generate
```

4. Run database migrations:

```bash
php artisan migrate
```

5. Start the application:

```bash
composer run dev
```

The API will be available from the local Laravel URL, usually `http://localhost:8000`.

## Important Endpoints

The API base path is:

```text
/api/v1
```

Some main endpoints:

- `GET /api/v1/health`
- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `GET /api/v1/auth/me`
- `GET /api/v1/users`
- `GET /api/v1/teams`
- `GET /api/v1/projects`
- `GET /api/v1/projects/sidebar`
- `GET /api/v1/tasks`
- `POST /api/v1/tasks/reorder`
- `GET /api/v1/dashboard`
- `GET /api/v1/notifications`
- `GET /api/v1/activity-logs`
- `GET /api/v1/webhook-endpoints`

More complete endpoint documentation is available in `doc/prd-laravel/07-api-endpoints.md`.

## Storage Configuration

By default, the project can run locally with the configuration from `.env.example`. For production attachments and avatars, this project supports Cloudflare R2 through the following configuration:

```env
AVATAR_FILESYSTEM_DISK=r2
ATTACHMENT_FILESYSTEM_DISK=r2
R2_ACCESS_KEY_ID=
R2_SECRET_ACCESS_KEY=
R2_BUCKET=
R2_URL=
R2_ENDPOINT=
```

## Running Tests and Quality Checks

Run tests:

```bash
php artisan test --compact
```

Run the PHP formatter:

```bash
vendor/bin/pint --format agent
```

Run the full Composer quality check:

```bash
composer test
```

## Project Documentation

Product requirement and technical documentation live in the `doc/` folder.

Useful starting points:

- `doc/prd-laravel/01-overview.md`
- `doc/prd-laravel/02-technical-foundation.md`
- `doc/prd-laravel/05-feature-requirements.md`
- `doc/prd-laravel/07-api-endpoints.md`
- `doc/todo.md`

## Status

This project is the Laravel backend for Project Manager. The main focus of this repository is the API, data model, authorization, storage, notifications, reporting, webhooks, and automated tests. The main frontend can be built as a separate application that consumes this REST API.
