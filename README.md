## Admin User Setup

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

## Roles

### List Roles
**Endpoint:** `GET /api/roles`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Admin",
      "code": "admin",
      "description": "Administrator role",
      "created_at": "2025-04-24T10:20:00.000000Z",
      "updated_at": "2025-04-24T10:20:00.000000Z",
      "permissions_count": 15
    },
    {
      "id": 2,
      "name": "Staff",
      "code": "staff",
      "description": "Regular staff member",
      "created_at": "2025-04-24T10:20:00.000000Z",
      "updated_at": "2025-04-24T10:20:00.000000Z",
      "permissions_count": 5
    }
    // more roles
  ]
}
```

### Assign Permissions to Role
**Endpoint:** `POST /api/roles/{role}/permissions`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Request:**
```json
{
  "permission_ids": [1, 2, 3, 4]
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Permissions assigned successfully",
  "data": {
    "id": 2,
    "name": "Staff",
    "code": "staff",
    "description": "Regular staff member",
    "created_at": "2025-04-24T10:20:00.000000Z",
    "updated_at": "2025-04-24T10:20:00.000000Z",
    "permissions": [
      {
        "id": 1,
        "name": "View Users",
        "code": "users:view",
        "group": "users",
        "pivot": {
          "role_id": 2,
          "permission_id": 1
        }
      },
      // more permissions
    ]
  }
}
```

## Permissions

### List Permissions
**Endpoint:** `GET /api/permissions`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "View Users",
      "code": "users:view",
      "group": "users",
      "created_at": "2025-04-24T10:20:00.000000Z",
      "updated_at": "2025-04-24T10:20:00.000000Z"
    },
    {
      "id": 2,
      "name": "Create Users",
      "code": "users:create",
      "group": "users",
      "created_at": "2025-04-24T10:20:00.000000Z",
      "updated_at": "2025-04-24T10:20:00.000000Z"
    }
    // more permissions
  ]
}
```

### Get Permission Groups
**Endpoint:** `GET /api/permissions/groups`

**Headers:**
```
Authorization: Bearer {access_token}
```

**Response (200):**
```json
{
  "success": true,
  "data": [
    {
      "name": "users",
      "permissions": [
        {
          "id": 1,
          "name": "View Users",
          "code": "users:view",
          "group": "users"
        },
        {
          "id": 2,
          "name": "Create Users",
          "code": "users:create",
          "group": "users"
        }
        // more permissions in this group
      ]
    },
    {
      "name": "roles",
      "permissions": [
        // permissions in the roles group
      ]
    }
    // more groups
  ]
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