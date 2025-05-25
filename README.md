## Admin User Setup

BEFORE MIGRATING USE `php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="activitylog-migrations"`
Use the following commands in `php artisan tinker` to create the initial admin user:

```php
// Create admin role reference
$adminRole = App\Models\Role::where('code', 'admin')->first();

// Create admin user
$admin = App\Models\User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => Hash::make('password'),
    'status' => 'active'
]);

// Assign admin role to user
$admin->roles()->attach($adminRole->id);

exit;
```

This will create an admin user with the email `admin@example.com` and password `password`.

# API Documentation

## Authentication

### Login
**Endpoint:** `POST /api/auth/login`

**Request:**
```json
{
  "email": "admin@example.com",
  "password": "password"
}
```

**Response (200):**
```json
{
  "success": true,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "phone": null,
    "status": "active",
    "created_at": "2025-04-24T10:30:00.000000Z",
    "updated_at": "2025-04-24T10:30:00.000000Z",
    "roles": [
      {
        "id": 1,
        "name": "Admin",
        "code": "admin",
        "description": "Administrator role with all permissions",
        "created_at": "2025-04-24T10:20:00.000000Z",
        "updated_at": "2025-04-24T10:20:00.000000Z",
        "permissions": [
          {
            "id": 1,
            "name": "View Users",
            "code": "users:view",
            "group": "users",
            "created_at": "2025-04-24T10:20:00.000000Z",
            "updated_at": "2025-04-24T10:20:00.000000Z"
          }
          // other permissions
        ]
      }
    ]
  }
}
```

**Response (401 - Invalid Credentials):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

**Response (403 - Inactive Account):**
```json
{
  "success": false,
  "message": "Your account is inactive. Please contact the administrator."
}
```

### Register
**Endpoint:** `POST /api/auth/register`

**Request:**
```json
{
  "name": "New User",
  "email": "new@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "123456789"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "User successfully registered",
  "data": {
    "name": "New User",
    "email": "new@example.com",
    "phone": "123456789",
    "status": "pending",
    "updated_at": "2025-04-24T14:30:00.000000Z",
    "created_at": "2025-04-24T14:30:00.000000Z",
    "id": 2
  }
}
```

### Logout
**Endpoint:** `POST /api/auth/logout`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Successfully logged out"
}
```

### Get User Profile
**Endpoint:** `GET /api/auth/me`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "phone": null,
    "status": "active",
    "created_at": "2025-04-24T10:30:00.000000Z",
    "updated_at": "2025-04-24T10:30:00.000000Z",
    "roles": [
      {
        "id": 1,
        "name": "Admin",
        "code": "admin",
        "description": "Administrator role with all permissions",
        "created_at": "2025-04-24T10:20:00.000000Z",
        "updated_at": "2025-04-24T10:20:00.000000Z",
        "permissions": [
          // permissions array
        ]
      }
    ]
  }
}
```

### Refresh Token
**Endpoint:** `POST /api/auth/refresh`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "token_type": "bearer",
  "expires_in": 3600,
  "user": {
    // user object with roles and permissions
  }
}
```

## Users

### List Users
**Endpoint:** `GET /api/users`

**Query Parameters:**
- `per_page`: Number of records per page (default: 15)
- `page`: Page number
- `sort_by`: Field to sort by (default: created_at)
- `sort_direction`: asc or desc (default: desc)
- `search`: Search term for name/email
- `status`: Filter by status
- `role_id`: Filter by role

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Users retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "name": "Admin User",
        "email": "admin@example.com",
        "phone": null,
        "status": "active",
        "created_at": "2025-04-24T10:30:00.000000Z",
        "updated_at": "2025-04-24T10:30:00.000000Z",
        "roles": [
          {
            "id": 1,
            "name": "Admin",
            "code": "admin"
          }
        ]
      },
      // more users
    ],
    "pagination": {
      "total": 10,
      "current_page": 1,
      "per_page": 15,
      "last_page": 1
    }
  }
}
```

### Get User
**Endpoint:** `GET /api/users/{id}`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "phone": null,
    "status": "active",
    "created_at": "2025-04-24T10:30:00.000000Z",
    "updated_at": "2025-04-24T10:30:00.000000Z",
    "roles": [
      {
        "id": 1,
        "name": "Admin",
        "code": "admin",
        "pivot": {
          "user_id": 1,
          "role_id": 1
        }
      }
    ]
  }
}
```

### Create User
**Endpoint:** `POST /api/users`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Request:**
```json
{
  "name": "New Staff",
  "email": "staff@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "987654321",
  "status": "active",
  "role_ids": [2]
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "User created successfully",
  "data": {
    "name": "New Staff",
    "email": "staff@example.com",
    "phone": "987654321",
    "status": "active",
    "updated_at": "2025-04-24T15:30:00.000000Z",
    "created_at": "2025-04-24T15:30:00.000000Z",
    "id": 3,
    "roles": [
      {
        "id": 2,
        "name": "Staff",
        "code": "staff",
        "pivot": {
          "user_id": 3,
          "role_id": 2
        }
      }
    ]
  }
}
```

### Update User
**Endpoint:** `PUT /api/users/{id}`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Request:**
```json
{
  "name": "Updated Name",
  "email": "staff@example.com",
  "phone": "111222333",
  "status": "active"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "User updated successfully",
  "data": {
    "id": 3,
    "name": "Updated Name",
    "email": "staff@example.com",
    "phone": "111222333",
    "status": "active",
    "updated_at": "2025-04-24T16:30:00.000000Z",
    "created_at": "2025-04-24T15:30:00.000000Z"
  }
}
```

### Delete User
**Endpoint:** `DELETE /api/users/{id}`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "User deleted successfully"
}
```

### Assign Roles to User
**Endpoint:** `POST /api/users/{user}/roles`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Request:**
```json
{
  "role_ids": [2, 3]
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Roles assigned successfully",
  "data": {
    "id": 3,
    "name": "Updated Name",
    "email": "staff@example.com",
    "roles": [
      {
        "id": 2,
        "name": "Staff",
        "code": "staff",
        "pivot": {
          "user_id": 3,
          "role_id": 2
        }
      },
      {
        "id": 3,
        "name": "Manager",
        "code": "manager",
        "pivot": {
          "user_id": 3,
          "role_id": 3
        }
      }
    ]
  }
}
```

## Role Management

### List Roles
**Endpoint:** `GET /api/roles`

**Query Parameters:**
- `search`: Search term for role name, code, or description
- `page`: Page number (default: 1)
- `per_page`: Items per page (default: 15)
- `sort_by`: Field to sort by (default: 'name')
- `sort_direction`: Sort direction ('asc' or 'desc', default: 'asc')

**Headers:**
```
Authorization: Bearer {access_token}
```

**Required Permission:** `roles:view`

**Response (200):**
```json
{
  "success": true,
  "message": "Roles retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "name": "Administrator",
        "code": "admin",
        "description": "Full system access",
        "created_at": "2025-04-24T15:04:59.000000Z",
        "updated_at": "2025-04-24T15:04:59.000000Z",
        "permissions_count": 27,
        "permissions": [
          {
            "id": 1,
            "name": "View Users",
            "code": "users:view",
            "group": "users",
            "description": "View Users permission"
          }
          // More permissions...
        ]
      }
      // More roles...
    ],
    "pagination": {
      "total": 5,
      "count": 5,
      "per_page": 15,
      "current_page": 1,
      "total_pages": 1
    }
  }
}
```

### Get Single Role

**Endpoint:** `GET /api/roles/{id}`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Required Permission:** `roles:view`

**Response (200):**
```json
{
  "success": true,
  "message": "Role retrieved successfully",
  "data": {
    "id": 2,
    "name": "Doctor",
    "code": "doctor",
    "description": "Healthcare provider with patient access",
    "created_at": "2025-04-24T15:04:59.000000Z",
    "updated_at": "2025-04-24T15:04:59.000000Z",
    "permissions": [
      {
        "id": 11,
        "name": "View Patients",
        "code": "patients:view",
        "group": "patients",
        "description": "View Patients permission"
      }
      // More permissions...
    ]
  }
}
```

### Create Role

**Endpoint:** `POST /api/roles`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Required Permission:** `roles:create`

**Request Body:**
```json
{
  "name": "Clinical Assistant",
  "code": "clinical_assistant",
  "description": "Assists doctors with patient management",
  "permissions": [13, 14, 15, 21]
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Role created successfully",
  "data": {
    "id": 6,
    "name": "Clinical Assistant",
    "code": "clinical_assistant",
    "description": "Assists doctors with patient management",
    "created_at": "2025-05-23T10:15:30.000000Z",
    "updated_at": "2025-05-23T10:15:30.000000Z",
    "permissions": [
      {
        "id": 13,
        "name": "View Patients",
        "code": "patients:view",
        "group": "patients",
        "description": "View Patients permission"
      }
      // More permissions...
    ]
  }
}
```

**Important Notes:**
- The `code` should be URL-friendly, lowercase, and use underscores instead of spaces
- The `permissions` array contains permission IDs to assign to the role

### Update Role

**Endpoint:** `PUT /api/roles/{id}`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Required Permission:** `roles:edit`

**Request Body:**
```json
{
  "name": "Clinical Assistant",
  "description": "Assists doctors with patient management and records",
  "permissions": [13, 14, 15, 21, 22]
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Role updated successfully",
  "data": {
    "id": 6,
    "name": "Clinical Assistant",
    "code": "clinical_assistant",
    "description": "Assists doctors with patient management and records",
    "created_at": "2025-05-23T10:15:30.000000Z",
    "updated_at": "2025-05-23T10:30:45.000000Z",
    "permissions": [
      // Updated permissions list
    ]
  }
}
```

**Important Notes:**
- You can update role details and permissions in one request
- Protected roles (admin, doctor, patient, guest) may have restrictions on renaming or code changes

### Delete Role

**Endpoint:** `DELETE /api/roles/{id}`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Required Permission:** `roles:delete`

**Response (200):**
```json
{
  "success": true,
  "message": "Role deleted successfully"
}
```

**Important Notes:**
- This endpoint will return a 403 error if you try to delete protected roles
- The backend detaches role associations with users and permissions before deletion

### Check if Role Name Exists

**Endpoint:** `GET /api/roles/check-name`

**Query Parameters:**
- `name`: Name to check
- `excludeId`: (Optional) Role ID to exclude from check (for edits)

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "exists": true  // or false if name doesn't exist
}
```

### Assign Permissions to Role

**Endpoint:** `POST /api/roles/{role}/permissions`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Required Permission:** `roles:assign-permissions`

**Request Body:**
```json
{
  "permissions": [1, 2, 3, 13, 14, 15]
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Permissions assigned successfully",
  "data": {
    "id": 2,
    "name": "Doctor",
    "code": "doctor",
    "description": "Healthcare provider with patient access",
    "created_at": "2025-04-24T15:04:59.000000Z",
    "updated_at": "2025-05-23T11:20:15.000000Z",
    "permissions": [
      // Updated permissions list
    ]
  }
}
```

## Permissions

### List Permissions
**Endpoint:** `GET /api/permissions`

**Query Parameters:**
- `search`: Filter permissions by name or code
- `group`: Filter permissions by group name
- `per_page`: Items per page (default: 50)
- `sort_by`: Field to sort by (default: 'group')
- `sort_direction`: Sort direction ('asc' or 'desc')

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Permissions retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1,
        "name": "View Users",
        "code": "users:view",
        "group": "users",
        "description": "View Users permission"
      },
      {
        "id": 2,
        "name": "Create Users",
        "code": "users:create",
        "group": "users",
        "description": "Create Users permission"
      }
      // more permissions
    ],
    "pagination": {
      "total": 30,
      "count": 30,
      "per_page": 50,
      "current_page": 1,
      "total_pages": 1
    }
  }
}
```

### Get Permissions Grouped by Category
**Endpoint:** `GET /api/permissions/groups`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Permission groups retrieved successfully",
  "data": {
    "users": [
      {
        "id": 1,
        "name": "View Users",
        "code": "users:view",
        "group": "users",
        "description": "View Users permission"
      },
      {
        "id": 2,
        "name": "Create Users",
        "code": "users:create",
        "group": "users",
        "description": "Create Users permission"
      }
      // More user permissions
    ],
    "roles": [
      // Role permissions
    ],
    "patients": [
      // Patient permissions
    ]
    // More groups
  }
}
```

### Get Permission Groups List
**Endpoint:** `GET /api/permissions/groups/list`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Permission groups list retrieved successfully",
  "data": ["users", "roles", "patients", "ai", "logs", "analytics"]
}
```

## Patients

### Create Patient
**Endpoint:** `POST /api/patients`

**Request:**
```json
{
  "name": "Patient Name",
  "email": "patient@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "phone": "555666777"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Patient created successfully",
  "data": {
    "name": "Patient Name",
    "email": "patient@example.com",
    "phone": "555666777",
    "status": "active",
    "updated_at": "2025-04-24T17:30:00.000000Z",
    "created_at": "2025-04-24T17:30:00.000000Z",
    "id": 4,
    "roles": [
      {
        "id": 4,
        "name": "Patient",
        "code": "patient",
        "pivot": {
          "user_id": 4,
          "role_id": 4
        }
      }
    ]
  }
}
```

### List Patients
**Endpoint:** `GET /api/patients`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Patients retrieved successfully",
  "data": {
    "items": [
      {
        "id": 4,
        "name": "Patient Name",
        "email": "patient@example.com",
        "phone": "555666777",
        "status": "active",
        "created_at": "2025-04-24T17:30:00.000000Z",
        "updated_at": "2025-04-24T17:30:00.000000Z"
      }
      // more patients
    ],
    "pagination": {
      "total": 1,
      "current_page": 1,
      "per_page": 15,
      "last_page": 1
    }
  }
}
```

## Activity Logs

### List Activity Logs
**Endpoint:** `GET /api/logs`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Query Parameters:**
- `per_page`: Number of records per page (default: 15)
- `page`: Page number
- `sort_by`: Field to sort by (default: created_at)
- `sort_direction`: asc or desc (default: desc)
- `user_id`: Filter by user
- `action`: Filter by action
- `module`: Filter by module

**Response (200):**
```json
{
  "success": true,
  "data": {
    "items": [
      {
        "id": 1,
        "user_id": 1,
        "action": "login",
        "module": "Authentication",
        "description": "User logged in",
        "entity_type": null,
        "entity_id": null,
        "ip_address": "127.0.0.1",
        "created_at": "2025-04-24T18:30:00.000000Z",
        "updated_at": "2025-04-24T18:30:00.000000Z",
        "user": {
          "id": 1,
          "name": "Admin User",
          "email": "admin@example.com"
        }
      }
      // more logs
    ],
    "pagination": {
      "total": 50,
      "current_page": 1,
      "per_page": 15,
      "last_page": 4
    }
  }
}
```

### Get User Logs
**Endpoint:** `GET /api/activity-logs/users/{user}`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Query Parameters:**
- `per_page`: Number of records per page (default: 20)
- `page`: Page number
- `sort_by`: Field to sort by (default: created_at)
- `sort_direction`: asc or desc (default: desc)

**Response (200):**
```json
{
  "success": true,
  "message": "User activity logs retrieved successfully",
  "data": {
    "items": [
      {
        "id": 23,
        "user_id": 1,
        "action": "login",
        "module": "Authentication",
        "description": "User logged in",
        "entity_type": null,
        "entity_id": null,
        "ip_address": "127.0.0.1",
        "created_at": "2025-04-25T10:30:00.000000Z",
        "updated_at": "2025-04-25T10:30:00.000000Z"
      }
      // more logs for this user
    ],
    "pagination": {
      "total": 15,
      "current_page": 1,
      "per_page": 20,
      "last_page": 1
    }
  }
}
```

### Get Logs by Action Type
**Endpoint:** `GET /api/activity-logs/actions/{action}`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Query Parameters:**
- `per_page`: Number of records per page (default: 20)
- `page`: Page number
- `sort_by`: Field to sort by (default: created_at)
- `sort_direction`: asc or desc (default: desc)

**Response (200):**
```json
{
  "success": true,
  "message": "Action logs retrieved successfully",
  "data": {
    "items": [
      {
        "id": 15,
        "user_id": 1,
        "action": "login",
        "module": "Authentication",
        "description": "User logged in",
        "entity_type": null,
        "entity_id": null,
        "ip_address": "127.0.0.1",
        "created_at": "2025-04-25T09:15:00.000000Z",
        "updated_at": "2025-04-25T09:15:00.000000Z",
        "user": {
          "id": 1,
          "name": "Admin User",
          "email": "admin@example.com"
        }
      }
      // more logs with this action
    ],
    "pagination": {
      "total": 30,
      "current_page": 1,
      "per_page": 20,
      "last_page": 2
    }
  }
}
```

### Get Logs by Module
**Endpoint:** `GET /api/activity-logs/modules/{module}`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Query Parameters:**
- `per_page`: Number of records per page (default: 20)
- `page`: Page number
- `sort_by`: Field to sort by (default: created_at)
- `sort_direction`: asc or desc (default: desc)

**Response (200):**
```json
{
  "success": true,
  "message": "Module logs retrieved successfully",
  "data": {
    "items": [
      {
        "id": 8,
        "user_id": 1,
        "action": "create",
        "module": "Users",
        "description": "Created user: John Doe",
        "entity_type": "User",
        "entity_id": 5,
        "ip_address": "127.0.0.1",
        "created_at": "2025-04-25T11:20:00.000000Z",
        "updated_at": "2025-04-25T11:20:00.000000Z",
        "user": {
          "id": 1,
          "name": "Admin User",
          "email": "admin@example.com"
        }
      }
      // more logs for this module
    ],
    "pagination": {
      "total": 12,
      "current_page": 1,
      "per_page": 20,
      "last_page": 1
    }
  }
}
```

### Get All Action Types
**Endpoint:** `GET /api/activity-logs/actions`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Log actions retrieved successfully",
  "data": [
    "login",
    "logout",
    "create",
    "update",
    "delete",
    "assign"
  ]
}
```

### Get All Module Types
**Endpoint:** `GET /api/activity-logs/modules`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Log modules retrieved successfully",
  "data": [
    "Authentication",
    "Users",
    "Roles",
    "Permissions",
    "Patients"
  ]
}
```

## Analytics

### User Statistics
**Endpoint:** `GET /api/analytics/users`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total_users": 4,
    "active_users": 3,
    "pending_users": 1,
    "users_by_role": {
      "admin": 1,
      "staff": 1,
      "manager": 1,
      "patient": 1
    },
    "recent_registrations": [
      // Recent 5 user registrations
    ]
  }
}
```

### Export Data
**Endpoint:** `GET /api/analytics/export/{type}`

**Parameters:**
- `type`: Type of export (users, roles, logs)

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response:** CSV file download