# API Endpoint MVP

> Bagian dari [PRD: Project Manager Laravel](../prd-laravel.md).

## 20. API Endpoint MVP

### Auth

- `POST /api/v1/auth/register`
- `POST /api/v1/auth/login`
- `POST /api/v1/auth/refresh`
- `POST /api/v1/auth/logout`
- `GET /api/v1/auth/me`

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
