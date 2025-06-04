```php
composer install
# Copy the example environment file
cp .env.example .env

# Generate Laravel application key
php artisan key:generate

# Generate JWT secret key
php artisan jwt:secret

# Run database migrations
php artisan migrate

# Seed the database with initial data (if available)
php artisan db:seed
```

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

## Bills API Documentation

### Access Control
- **Staff/Receptionists**: Full access to bill management (requires `bills:manage` permission)
- **Patients**: Read-only access to their own bills
- **Doctors**: No direct bill access (view through analytics only)

### Endpoints

#### Staff/Receptionist Endpoints

##### List All Bills
**Endpoint:** `GET /api/bills`

**Permission Required:** `bills:manage`

**Description:** Retrieves bills with powerful filtering capabilities

**Query Parameters:**
| Parameter | Type | Description | Example |
|-----------|------|-------------|---------|
| doctor_id | integer/string | Filter by doctor ID(s), comma-separated | `2,5,6` |
| doctor_name | string | Filter by doctor name(s), comma-separated | `Test Doctor,Michael Chen` |
| patient_id | integer | Filter by patient ID | `209` |
| date_from | date | Filter bills from this date (YYYY-MM-DD) | `2025-05-01` |
| date_to | date | Filter bills until this date (YYYY-MM-DD) | `2025-06-30` |
| preset_period | string | Use preset period (week, month, year) | `month` |
| amount_min | decimal | Minimum bill amount | `500` |
| amount_max | decimal | Maximum bill amount | `2000` |
| payment_method | string | Filter by payment method(s), comma-separated | `cash,insurance` |
| service_type | string | Filter by service type(s), comma-separated | `SURGERY,LAB` |
| sort_by | string | Field to sort by | `amount`, `issue_date`, `created_at` |
| sort_direction | string | Sort direction | `asc`, `desc` (default) |
| per_page | integer | Items per page | `10` (default: 15) |
| page | integer | Page number | `1` |

**Response (200):**
```json
{
  "success": true,
  "message": "Bills retrieved successfully",
  "data": {
    "items": [
      {
        "id": 1576,
        "patient_id": 209,
        "doctor_user_id": 2,
        "bill_number": "EDGE-20250601-215050-1cc6d",
        "amount": "16300.00",
        "issue_date": "2025-06-01T00:00:00.000000Z",
        "payment_method": "insurance",
        "description": "Complex surgical procedure with specialized care",
        "pdf_path": null,
        "created_by_user_id": 1,
        "created_at": "2025-06-01T21:50:50.000000Z",
        "updated_at": "2025-06-01T21:50:50.000000Z",
        "patient": { ... },
        "doctor": { ... },
        "items": [ ... ]
      }
      // More bills...
    ],
    "pagination": {
      "total": 120,
      "current_page": 1,
      "per_page": 15,
      "last_page": 8
    }
  }
}
```

##### Get Bills for Specific Patient
**Endpoint:** `GET /api/bills/by-patient/{patient}`

**Permission Required:** `bills:manage`

**Parameters:**
- `{patient}`: Patient ID

**Query Parameters:** Supports the same date, amount, and service filters as the main bills endpoint

**Response:** Same format as GET /api/bills

##### Create Bill
**Endpoint:** `POST /api/bills`

**Permission Required:** `bills:manage`

**Request Body:**
```json
{
  "patient_id": 209,
  "doctor_user_id": 5,
  "bill_number": "BILL-20250605-001",
  "issue_date": "2025-06-05",
  "payment_method": "insurance",
  "description": "Complete medical examination",
  "items": [
    { "service_type": "CHECKUP", "description": "General Health Checkup", "price": 200.00 },
    { "service_type": "LAB", "description": "Blood Work Panel", "price": 350.00 }
  ]
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Bill created successfully",
  "data": { ... }
}
```

##### Get Single Bill
**Endpoint:** `GET /api/bills/{bill}`

**Permission Required:** `bills:manage`

**Parameters:**
- `{bill}`: Bill ID

**Response (200):** Same format as bill object in the list response

##### Update Bill
**Endpoint:** `PUT /api/bills/{bill}`

**Permission Required:** `bills:manage`

**Parameters:**
- `{bill}`: Bill ID

**Request Body:**
```json
{
  "patient_id": 209,
  "doctor_user_id": 5,
  "bill_number": "BILL-20250605-001-REV",
  "issue_date": "2025-06-05",
  "payment_method": "credit_card",
  "description": "Complete medical examination with additional tests",
  "regenerate_pdf": true,
  "items": [ ... ]
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Bill updated successfully",
  "data": { ... }
}
```

##### Delete Bill
**Endpoint:** `DELETE /api/bills/{bill}`

**Permission Required:** `bills:manage`

**Parameters:**
- `{bill}`: Bill ID

**Response (200):**
```json
{
  "success": true,
  "message": "Bill deleted successfully"
}
```

##### Get Bill Items
**Endpoint:** `GET /api/bills/{bill}/items`

**Permission Required:** `bills:manage`

**Parameters:**
- `{bill}`: Bill ID

**Response (200):**
```json
{
  "success": true,
  "message": "Bill items retrieved successfully",
  "data": [ ... ]
}
```

##### Add Bill Item
**Endpoint:** `POST /api/bills/{bill}/items`

**Permission Required:** `bills:manage`

**Parameters:**
- `{bill}`: Bill ID

**Request Body:**
```json
{
  "service_type": "MEDS",
  "description": "Prescription Medication",
  "price": 85.00
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Item added to bill successfully",
  "data": { ... }
}
```

##### Update Bill Item
**Endpoint:** `PUT /api/bills/{bill}/items/{item}`

**Permission Required:** `bills:manage`

**Parameters:**
- `{bill}`: Bill ID
- `{item}`: Item ID

**Request Body:**
```json
{
  "service_type": "MEDS",
  "description": "Extended Prescription Medication",
  "price": 120.00
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Bill item updated successfully",
  "data": { ... }
}
```

##### Remove Bill Item
**Endpoint:** `DELETE /api/bills/{bill}/items/{item}`

**Permission Required:** `bills:manage`

**Parameters:**
- `{bill}`: Bill ID
- `{item}`: Item ID

**Response (200):**
```json
{
  "success": true,
  "message": "Bill item removed successfully"
}
```

##### Download Bill PDF
**Endpoint:** `GET /api/bills/{bill}/pdf`

**Permission Required:** `bills:manage`

**Parameters:**
- `{bill}`: Bill ID

**Response:** PDF file download

#### Patient Endpoints

##### Get My Bills
**Endpoint:** `GET /api/patient/bills`

**Access:** Patient role only (self-service)

**Query Parameters:**
- `date_from`, `date_to`, `sort_by`, `sort_direction`, `per_page`, `page`

**Response (200):** Same format as staff bill listing, but limited to the authenticated patient's bills

##### View Specific Bill
**Endpoint:** `GET /api/patient/bills/{bill}`

**Access:** Patient role only (self-service)

**Parameters:**
- `{bill}`: Bill ID

**Response (200):** Same format as bill object in the list response, but only accessible if the bill belongs to the authenticated patient

### Filtering Examples

#### Doctor Filtering
```http
GET {{base_url}}/api/bills?doctor_name=Dr. Emily Rodriguez&per_page=10
GET {{base_url}}/api/bills?doctor_id=6&per_page=10
GET {{base_url}}/api/bills?doctor_name=Test Doctor,Dr. Michael Chen,Dr. Emily Rodriguez&per_page=10
GET {{base_url}}/api/bills?doctor_id=2,5,6&per_page=10
```

#### Payment Method Filtering
```http
GET {{base_url}}/api/bills?payment_method=insurance&per_page=10
GET {{base_url}}/api/bills?payment_method=cash,bank_transfer,insurance&per_page=10
GET {{base_url}}/api/bills?payment_method=credit_card,bank_transfer,insurance&per_page=10
```

#### Service Type Filtering
```http
GET {{base_url}}/api/bills?service_type=SURGERY&per_page=10
GET {{base_url}}/api/bills?service_type=CHECKUP,CONSULT,XRAY&per_page=10
GET {{base_url}}/api/bills?service_type=SURGERY,SPECIALIST,LAB&per_page=10
GET {{base_url}}/api/bills?service_type=EMERGENCY,SURGERY&per_page=10
```

#### Amount Range Filtering
```http
GET {{base_url}}/api/bills?amount_min=500&per_page=10
GET {{base_url}}/api/bills?amount_max=500&per_page=10
GET {{base_url}}/api/bills?amount_min=500&amount_max=2000&per_page=10
GET {{base_url}}/api/bills?amount_min=10000&per_page=10
```

#### Date Range Filtering
```http
GET {{base_url}}/api/bills?date_from=2025-05-01&date_to=2025-05-31&per_page=10
GET {{base_url}}/api/bills?date_from=2025-05-01&per_page=10
GET {{base_url}}/api/bills?date_to=2025-06-30&per_page=10
GET {{base_url}}/api/bills?preset_period=month&per_page=10
```

#### Sorting and Pagination
```http
GET {{base_url}}/api/bills?sort_by=amount&sort_direction=desc&per_page=10
GET {{base_url}}/api/bills?sort_by=issue_date&sort_direction=asc&per_page=10
GET {{base_url}}/api/bills?sort_by=bill_number&sort_direction=asc&per_page=10
GET {{base_url}}/api/bills?per_page=5&page=2
```

#### Multi-Filter Complex Queries
```http
GET {{base_url}}/api/bills?payment_method=insurance&amount_min=1000&service_type=SURGERY,SPECIALIST,IMAGING&sort_by=amount&sort_direction=desc&per_page=10
GET {{base_url}}/api/bills?payment_method=cash&service_type=CHECKUP,CONSULT,VACCINE&date_from=2025-05-01&sort_by=issue_date&sort_direction=desc&per_page=10
GET {{base_url}}/api/bills?doctor_name=Dr. Michael Chen&service_type=EMERGENCY,SURGERY&amount_min=500&date_from=2025-05-01&date_to=2025-06-30&per_page=10
GET {{base_url}}/api/bills?doctor_name=Test Doctor,Dr. Michael Chen,Dr. Emily Rodriguez&payment_method=bank_transfer,insurance,credit_card&service_type=SURGERY,EMERGENCY,SPECIALIST,LAB,IMAGING&amount_min=500&amount_max=20000&date_from=2025-05-01&date_to=2025-06-30&sort_by=amount&sort_direction=desc&per_page=10&page=1
```

### Financial Analytics Endpoints

These endpoints provide aggregated financial data and require the `analytics:view` permission.

#### Revenue Analytics
**Endpoint:** `GET /api/analytics/revenue`
**Permission Required:** `analytics:view`

#### Service Analytics
**Endpoint:** `GET /api/analytics/services`
**Permission Required:** `analytics:view`

#### Doctor Revenue Analytics
**Endpoint:** `GET /api/analytics/doctor-revenue`
**Permission Required:** `analytics:view`

### Service Types
The system recognizes these predefined service types, which can be used for filtering:

| Code | Description |
|------|-------------|
| CHECKUP | General Checkup |
| CONSULT | General Consultation |
| XRAY | X-Ray Examination |
| LAB | Laboratory Tests |
| MEDS | Medication |
| PHYSIO | Physiotherapy |
| SURGERY | Surgical Procedure |
| DENTAL | Dental Work |
| THERAPY | Therapy Session |
| EMERGENCY | Emergency Care |
| IMAGING | Advanced Imaging |
| SPECIALIST | Specialist Consultation |
| VACCINE | Vaccination |
| REHAB | Rehabilitation |

### Payment Methods
- `cash`: Cash payments
- `credit_card`: Credit card payments
- `bank_transfer`: Bank transfers
- `insurance`: Insurance claims

### Security Notes
- All bill endpoints require authentication
- Staff/receptionists need the `bills:manage` permission
- Patients can only view their own bills
- Activity logging is enabled for all bill operations
