<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define permissions by group
        $permissions = [
            // User Management
            'users' => [
                ['name' => 'View Users', 'code' => 'users:view'],
                ['name' => 'Create Users', 'code' => 'users:create'],
                ['name' => 'Edit Users', 'code' => 'users:edit'],
                ['name' => 'Delete Users', 'code' => 'users:delete'],
                ['name' => 'Manage User Roles', 'code' => 'users:manage-roles'],
                ['name' => 'Reset User Password', 'code' => 'users:reset-password'],
            ],

            // Role Management
            'roles' => [
                ['name' => 'View Roles', 'code' => 'roles:view'],
                ['name' => 'Create Roles', 'code' => 'roles:create'],
                ['name' => 'Edit Roles', 'code' => 'roles:edit'],
                ['name' => 'Delete Roles', 'code' => 'roles:delete'],
                ['name' => 'Assign Permissions', 'code' => 'roles:assign-permissions'],
            ],

            // Patient Management
            'patients' => [
                ['name' => 'View Patients', 'code' => 'patients:view'],
                ['name' => 'Create Patients', 'code' => 'patients:create'],
                ['name' => 'Edit Patients', 'code' => 'patients:edit'],
                ['name' => 'Delete Patients', 'code' => 'patients:delete'],
                ['name' => 'View Patient Medical Data', 'code' => 'patients:view-medical'],
                ['name' => 'Edit Patient Medical Data', 'code' => 'patients:edit-medical'],
            ],

            // AI Models
            'ai' => [
                ['name' => 'View AI Models', 'code' => 'ai:view'],
                ['name' => 'Create AI Models', 'code' => 'ai:create'],
                ['name' => 'Edit AI Models', 'code' => 'ai:edit'],
                ['name' => 'Delete AI Models', 'code' => 'ai:delete'],
                ['name' => 'Assign AI Models to Users', 'code' => 'ai:assign'],
                ['name' => 'Use AI Models', 'code' => 'ai:use'],
            ],

            // Activity Logging
            'logs' => [
                ['name' => 'View Activity Logs', 'code' => 'logs:view'],
                ['name' => 'Export Logs', 'code' => 'logs:export'],
            ],

            // Analytics
            'analytics' => [
                ['name' => 'View Analytics', 'code' => 'analytics:view'],
                ['name' => 'Export Analytics', 'code' => 'analytics:export'],
            ],

            // Bill Management
            'bills' => [
                ['name' => 'Manage Bills', 'code' => 'bills:manage'],
                ['name' => 'View Bills', 'code' => 'bills:view'],
                ['name' => 'Create Bills', 'code' => 'bills:create'],
                ['name' => 'Edit Bills', 'code' => 'bills:edit'],
                ['name' => 'Delete Bills', 'code' => 'bills:delete'],
                ['name' => 'View Patient Bills', 'code' => 'bills:view-patient'],
                ['name' => 'Generate Bill PDF', 'code' => 'bills:generate-pdf'],
            ],
        ];

        // Create permissions
        $allPermissionIds = [];

        foreach ($permissions as $group => $items) {
            foreach ($items as $item) {
                $permission = Permission::updateOrCreate(
                    ['code' => $item['code']],
                    [
                        'name' => $item['name'],
                        'group' => $group,
                        'description' => $item['name'] . ' permission',
                    ]
                );

                $allPermissionIds[] = $permission->id;
            }
        }

        // Assign all permissions to admin role
        $adminRole = Role::where('code', 'admin')->first();
        if ($adminRole) {
            $adminRole->permissions()->sync($allPermissionIds);
        }

        // Assign specific permissions to doctor role
        $doctorRole = Role::where('code', 'doctor')->first();
        if ($doctorRole) {
            $doctorPermissions = Permission::whereIn('code', [
                'patients:view',
                'patients:create',
                'patients:edit',
                'patients:view-medical',
                'patients:edit-medical',
                'ai:use',
                'bills:view',
                'bills:create'
            ])->pluck('id')->toArray();

            $doctorRole->permissions()->sync($doctorPermissions);
        }

        // Assign limited permissions to nurse role
        $nurseRole = Role::where('code', 'nurse')->first();
        if ($nurseRole) {
            $nursePermissions = Permission::whereIn('code', [
                'patients:view',
                'patients:view-medical',
                'ai:use'
            ])->pluck('id')->toArray();

            $nurseRole->permissions()->sync($nursePermissions);
        }

        // Assign limited permissions to receptionist role
        $receptionistRole = Role::where('code', 'receptionist')->first();
        if ($receptionistRole) {
            $receptionistPermissions = Permission::whereIn('code', [
                'patients:view',
                'patients:create',
                'patients:edit',
                'bills:manage',
                'bills:view',
                'bills:create',
                'bills:edit',
                'bills:view-patient',
                'bills:generate-pdf'
            ])->pluck('id')->toArray();

            $receptionistRole->permissions()->sync($receptionistPermissions);
        }
    }
}
