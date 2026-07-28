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
- `GET /api/v1/projects/sidebar`
- `POST /api/v1/projects`
- `GET /api/v1/projects/{project}`
- `PATCH /api/v1/projects/{project}`
- `DELETE /api/v1/projects/{project}`
- `GET /api/v1/projects/{project}/tasks`
- `GET /api/v1/projects/{project}/activity`
- `GET /api/v1/projects/{project}/summary`
- `POST /api/v1/projects/{project}/teams`
- `DELETE /api/v1/projects/{project}/teams/{team}`

#### `GET /api/v1/projects/sidebar`

Mengembalikan daftar project ringan untuk sidebar sesuai visibility scope user.

Response `200 OK`:

```json
{
  "data": [
    {
      "id": 1,
      "name": "Website redesign"
    }
  ]
}
```

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

#### `GET /api/v1/dashboard`

Mengembalikan ringkasan dashboard sesuai visibility scope user. Admin melihat semua project/task, sedangkan user non-admin hanya melihat project/task yang dimiliki, dibuat, di-assign langsung, di-assign melalui team, atau terkait task yang menjadi tanggung jawabnya.

Response `200 OK`:

```json
{
  "totalActiveProjects": 3,
  "taskCountPerStatus": {
    "backlog": 2,
    "todo": 8,
    "in_progress": 4,
    "review": 3,
    "done": 15
  },
  "activeProgress": {
    "doing": 4,
    "todo": 8,
    "total": 12,
    "ratio": 0.3333,
    "percentage": 33.33
  },
  "inReview": 3,
  "dueSoon": 5,
  "overdue": 2,
  "overdueTaskCount": 2,
  "workloadPerMember": {
    "1": 7,
    "2": 4
  },
  "recentlyUpdatedTasks": [],
  "recentTasks": [],
  "upcomingDeadlines": [],
  "highPriorityTasks": [],
  "latestUpdates": []
}
```

Field notes:

- `activeProgress.doing` menghitung task status `in_progress`.
- `activeProgress.todo` menghitung task status `todo`.
- `activeProgress.total` adalah `todo + doing`.
- `activeProgress.ratio` adalah `doing / (todo + doing)`, dibulatkan 4 digit desimal. Jika total `0`, nilainya `0.0`.
- `activeProgress.percentage` adalah ratio dalam persen, dibulatkan 2 digit desimal. Jika total `0`, nilainya `0.0`.
- `inReview` menghitung task status `review`.
- `dueSoon` menghitung task belum `done` dengan `due_date` dari hari ini sampai 7 hari ke depan.
- `overdue` menghitung task belum `done` dengan `due_date` sebelum hari ini.
- `overdueTaskCount` dipertahankan sebagai alias kompatibilitas untuk `overdue`.
- `workloadPerMember` berisi jumlah task per `assignee_id`.
- `recentlyUpdatedTasks` berisi maksimal 5 task terbaru berdasarkan `updated_at`.
- `recentTasks` berisi maksimal 5 task terbaru berdasarkan `created_at`.
- `upcomingDeadlines` berisi maksimal 5 task belum `done` dengan deadline terdekat dari hari ini.
- `highPriorityTasks` berisi maksimal 5 task belum `done` dengan priority `urgent` atau `high`, diurutkan `urgent` lebih dulu.
- `latestUpdates` berisi maksimal 5 activity log terbaru yang terlihat oleh user.

Item pada `recentlyUpdatedTasks`, `recentTasks`, `upcomingDeadlines`, dan `highPriorityTasks` memakai struktur `TaskResource`. Item pada `latestUpdates` memakai struktur `ActivityLogResource`.
