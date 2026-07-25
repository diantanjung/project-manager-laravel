# Core Domain Model dan Enum

> Bagian dari [PRD: Project Manager Laravel](../prd-laravel.md).

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

### 11.4 Project

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

### 11.5 Task

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

### 11.6 Comment

- `id`
- `task_id`
- `author_id`
- `content`
- `created_at`
- `updated_at`

### 11.7 Attachment

- `id`
- `task_id`
- `uploader_id`
- `disk`
- `path`
- `original_name`
- `mime_type`
- `size`
- `created_at`

### 11.8 Notification

- `id`
- `recipient_id`
- `actor_id`
- `task_id`
- `type`
- `title`
- `message`
- `read_at`
- `created_at`

### 11.9 ActivityLog

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

### 11.10 WebhookEndpoint

- `id`
- `name`
- `url`
- `secret`
- `events`
- `is_active`
- `created_at`
- `updated_at`

### 11.11 WebhookDelivery

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

### 11.12 Pivot / Intermediate Tables

Tabel berikut bukan model domain Eloquent terpisah. Sesuai konsep many-to-many Laravel, data tambahan di tabel intermediate diakses melalui relasi `belongsToMany()` dan atribut `pivot` dengan `withPivot()`. Jangan membuat `App\Models\TeamMember`, `App\Models\ProjectTeam`, atau `App\Models\TaskAssignment` kecuali nantinya benar-benar diperlukan custom pivot model yang extend `Illuminate\Database\Eloquent\Relations\Pivot`.

#### team_members

Relasi:

- `Team::members()`
- `User::teams()`

Kolom:

- `id`
- `team_id`
- `user_id`
- `role`
- `joined_at`

#### project_teams

Relasi:

- `Project::assignedTeams()`
- `Team::assignedProjects()`

Kolom:

- `id`
- `project_id`
- `team_id`
- `assigned_at`

#### task_assignments

Relasi:

- `Task::assignedUsers()`

Kolom:

- `task_id`
- `user_id`
- `assigned_by`
- `created_at`
- `updated_at`

## 12. Enum

```text
UserRole           = admin | productOwner | projectManager | teamMember
TeamMembershipRole = owner | admin | member
ProjectStatus      = planning | active | paused | completed | archived
TaskStatus         = backlog | todo | in_progress | review | done
TaskPriority       = low | medium | high | urgent
NotificationType   = task_assigned | mention | task_due | project_update | system_alert
WebhookEvent       = task.created | task.updated | task.completed | comment.created | project.updated
```
