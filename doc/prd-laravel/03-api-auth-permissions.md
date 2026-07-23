# API Design, Auth, dan Permissions

> Bagian dari [PRD: Project Manager Laravel](../prd-laravel.md).

## 9. API Design

### 9.1 Prefix dan Format

- Prefix utama: `/api/v1`.
- Request/response: JSON.
- Upload file: `multipart/form-data`.
- Authenticated endpoint menggunakan:

```http
Authorization: Bearer <accessToken>
```

### 9.2 Response Envelope

Single resource:

```json
{
  "data": {}
}
```

Paginated list:

```json
{
  "data": [],
  "pagination": {
    "page": 1,
    "limit": 20,
    "totalItems": 0,
    "totalPages": 0
  }
}
```

Error:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

### 9.3 Field Naming

- Database menggunakan snake_case.
- API response menggunakan camelCase.
- Timestamp menggunakan ISO 8601.
- Enum value menggunakan string stabil dan terdokumentasi.

## 10. Roles dan Permissions

Role hierarchy:

```text
teamMember < projectManager < productOwner < admin
```

| Capability | Team Member | Project Manager | Product Owner | Admin |
| --- | :---: | :---: | :---: | :---: |
| View assigned projects/tasks | Yes | Yes | Yes | Yes |
| Create comment | Yes | Yes | Yes | Yes |
| Upload attachment | Yes | Yes | Yes | Yes |
| Update own task status | Yes | Yes | Yes | Yes |
| Create task | No | Yes | Yes | Yes |
| Assign task | No | Yes | Yes | Yes |
| Create project | No | Yes | Yes | Yes |
| Manage team members | No | Yes | Yes | Yes |
| Create team | No | No | Yes | Yes |
| Manage users | No | No | No | Yes |
| View audit log | No | Project scope | Product scope | Global |
| Manage system settings | No | No | No | Yes |
