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
                ['name' => 'Assign User Roles', 'code' => 'users:assign-roles'],
                ['name' => 'Revoke User Roles', 'code' => 'users:revoke-roles'],
                ['name' => 'View User Permissions', 'code' => 'users:view-permissions'],
                ['name' => 'Bulk Manage Users', 'code' => 'users:bulk-manage'],
                ['name' => 'Export Users', 'code' => 'users:export'],
                ['name' => 'Import Users', 'code' => 'users:import'],
            ],

            // Role Management
            'roles' => [
                ['name' => 'View Roles', 'code' => 'roles:view'],
                ['name' => 'Create Roles', 'code' => 'roles:create'],
                ['name' => 'Edit Roles', 'code' => 'roles:edit'],
                ['name' => 'Delete Roles', 'code' => 'roles:delete'],
                ['name' => 'Assign Permissions', 'code' => 'roles:assign-permissions'],
                ['name' => 'Revoke Permissions', 'code' => 'roles:revoke-permissions'],
                ['name' => 'View Role Users', 'code' => 'roles:view-users'],
                ['name' => 'View Role Permissions', 'code' => 'roles:view-permissions'],
                ['name' => 'View Role Hierarchy', 'code' => 'roles:view-hierarchy'],
            ],

            // Permission Management
            'permissions' => [
                ['name' => 'View Permissions', 'code' => 'permissions:view'],
                ['name' => 'Create Permissions', 'code' => 'permissions:create'],
                ['name' => 'Edit Permissions', 'code' => 'permissions:edit'],
                ['name' => 'Delete Permissions', 'code' => 'permissions:delete'],
                ['name' => 'View Permission Groups', 'code' => 'permissions:view-groups'],
                ['name' => 'View Permission Matrix', 'code' => 'permissions:view-matrix'],
            ],

            // Patient Management
            'patients' => [
                ['name' => 'View Patients', 'code' => 'patients:view'],
                ['name' => 'Create Patients', 'code' => 'patients:create'],
                ['name' => 'Edit Patients', 'code' => 'patients:edit'],
                ['name' => 'Delete Patients', 'code' => 'patients:delete'],
                ['name' => 'View Patient Medical Data', 'code' => 'patients:view-medical'],
                ['name' => 'Edit Patient Medical Data', 'code' => 'patients:edit-medical'],
                ['name' => 'View Patient Files', 'code' => 'patients:view-files'],
                ['name' => 'Manage Patient Files', 'code' => 'patients:manage-files'],
                ['name' => 'View Patient Notes', 'code' => 'patients:view-notes'],
                ['name' => 'Manage Patient Notes', 'code' => 'patients:manage-notes'],
                ['name' => 'Manage All Patient Notes', 'code' => 'patients:manage-all-notes'],
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
                ['name' => 'View User Logs', 'code' => 'logs:view-user'],
                ['name' => 'View Action Logs', 'code' => 'logs:view-actions'],
                ['name' => 'View Module Logs', 'code' => 'logs:view-modules'],
                ['name' => 'View Security Logs', 'code' => 'logs:view-security'],
                ['name' => 'View Failure Logs', 'code' => 'logs:view-failures'],
                ['name' => 'View Real-time Logs', 'code' => 'logs:view-realtime'],
            ],

            // Analytics
            'analytics' => [
                ['name' => 'View Analytics', 'code' => 'analytics:view'],
                ['name' => 'Export Analytics', 'code' => 'analytics:export'],
                ['name' => 'View User Analytics', 'code' => 'analytics:users'],
                ['name' => 'View Auth Analytics', 'code' => 'analytics:auth'],
                ['name' => 'View System Analytics', 'code' => 'analytics:system'],
                ['name' => 'View Finance Analytics', 'code' => 'analytics:finance'],
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
                ['name' => 'Manage Bill Payments', 'code' => 'bills:manage-payments'],
                ['name' => 'Send Bills', 'code' => 'bills:send'],
                ['name' => 'View Bill Items', 'code' => 'bills:view-items'],
                ['name' => 'Bulk Manage Bills', 'code' => 'bills:bulk-manage'],
                ['name' => 'Manage Bill Templates', 'code' => 'bills:templates'],
                ['name' => 'Manage Recurring Bills', 'code' => 'bills:recurring'],
            ],

            // Finance Management
            'finance' => [
                ['name' => 'View Finance Overview', 'code' => 'finance:view'],
                ['name' => 'View Profits', 'code' => 'finance:profits'],
                ['name' => 'View Revenue', 'code' => 'finance:revenue'],
                ['name' => 'View Expenses', 'code' => 'finance:expenses'],
                ['name' => 'View Payments', 'code' => 'finance:payments'],
                ['name' => 'View Outstanding', 'code' => 'finance:outstanding'],
                ['name' => 'View Trends', 'code' => 'finance:trends'],
                ['name' => 'View Forecasting', 'code' => 'finance:forecasting'],
                ['name' => 'Export Finance Data', 'code' => 'finance:export'],
            ],

            // Appointment Management
            'appointments' => [
                ['name' => 'View All Appointments', 'code' => 'appointments:view-all'],
                ['name' => 'View Own Appointments', 'code' => 'appointments:view-own'],
                ['name' => 'Create Appointments', 'code' => 'appointments:create'],
                ['name' => 'Update Appointments', 'code' => 'appointments:update'],
                ['name' => 'Delete Appointments', 'code' => 'appointments:delete'],
                ['name' => 'Cancel Appointments', 'code' => 'appointments:cancel'],
                ['name' => 'Confirm Appointments', 'code' => 'appointments:confirm'],
                ['name' => 'Complete Appointments', 'code' => 'appointments:complete'],
                ['name' => 'View Available Slots', 'code' => 'appointments:view-slots'],
                ['name' => 'Manage Schedule', 'code' => 'appointments:manage-schedule'],
                ['name' => 'Block Time Slots', 'code' => 'appointments:block-slots'],
                ['name' => 'View Appointment Reports', 'code' => 'appointments:reports'],
                ['name' => 'Manage All Appointments', 'code' => 'appointments:manage'],
            ],

            // Reminder Management
            'reminders' => [
                ['name' => 'View Reminders', 'code' => 'reminders:view'],
                ['name' => 'Schedule Reminders', 'code' => 'reminders:schedule'],
                ['name' => 'Cancel Reminders', 'code' => 'reminders:cancel'],
                ['name' => 'Send Manual Reminders', 'code' => 'reminders:send-manual'],
                ['name' => 'Bulk Reminder Operations', 'code' => 'reminders:bulk'],
                ['name' => 'View Reminder Analytics', 'code' => 'reminders:analytics'],
                ['name' => 'Manage Reminder Settings', 'code' => 'reminders:manage-settings'],
            ],

            // Notification Management
            'notifications' => [
                ['name' => 'View Notifications', 'code' => 'notifications:view'],
                ['name' => 'Manage Notifications', 'code' => 'notifications:manage'],
                ['name' => 'Delete Notifications', 'code' => 'notifications:delete'],
                ['name' => 'Send Notifications', 'code' => 'notifications:send'],
                ['name' => 'Broadcast Notifications', 'code' => 'notifications:broadcast'],
            ],

            // System Management
            'system' => [
                ['name' => 'View System Health', 'code' => 'system:health'],
                ['name' => 'View System Config', 'code' => 'system:config'],
                ['name' => 'Clear Cache', 'code' => 'system:cache'],
                ['name' => 'Clear Logs', 'code' => 'system:logs'],
                ['name' => 'Create Backup', 'code' => 'system:backup'],
                ['name' => 'Maintenance Mode', 'code' => 'system:maintenance'],
            ],
        ];

        // Create permissions
        $this->command->info('Creating permissions...');
        
        foreach ($permissions as $group => $items) {
            foreach ($items as $item) {
                Permission::updateOrCreate(
                    ['code' => $item['code']],
                    [
                        'name' => $item['name'],
                        'group' => $group,
                        'description' => $item['name'] . ' permission',
                    ]
                );
            }
        }

        // ✅ FIXED: Assign ALL permissions to admin role (including ones from other seeders)
        $this->assignPermissionsToRoles();
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissionsToRoles()
    {
        $this->command->info('Assigning permissions to roles...');

        // ✅ ADMIN GETS ALL PERMISSIONS - This ensures admin always has everything
        $adminRole = Role::where('code', 'admin')->first();
        if ($adminRole) {
            // Get ALL permissions from database (including ones created by other seeders)
            $allPermissionIds = Permission::pluck('id')->toArray();
            $adminRole->permissions()->sync($allPermissionIds);
            $this->command->info("✅ Admin role assigned " . count($allPermissionIds) . " permissions");
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
                'patients:view-files',
                'patients:manage-files',
                'patients:view-notes',
                'patients:manage-notes',
                'ai:use',
                'bills:view',
                'bills:create',
                'appointments:view-own',
                'appointments:create',
                'appointments:update',
                'appointments:cancel',
                'appointments:confirm',
                'appointments:complete',
                'appointments:view-slots',
                'appointments:manage-schedule',
                'appointments:block-slots',
                'reminders:view',
                'reminders:schedule',
                'reminders:send-manual'
            ])->pluck('id')->toArray();

            $doctorRole->permissions()->sync($doctorPermissions);
            $this->command->info("✅ Doctor role assigned " . count($doctorPermissions) . " permissions");
        }

        // Assign limited permissions to nurse role
        $nurseRole = Role::where('code', 'nurse')->first();
        if ($nurseRole) {
            $nursePermissions = Permission::whereIn('code', [
                'patients:view',
                'patients:view-medical',
                'ai:use',
                'appointments:view-all',
                'appointments:create',
                'appointments:update',
                'appointments:cancel',
                'appointments:confirm',
                'appointments:complete',
                'reminders:view'
            ])->pluck('id')->toArray();

            $nurseRole->permissions()->sync($nursePermissions);
            $this->command->info("✅ Nurse role assigned " . count($nursePermissions) . " permissions");
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
                'bills:generate-pdf',
                'appointments:view-all',
                'appointments:create',
                'appointments:update',
                'appointments:cancel',
                'appointments:confirm',
                'appointments:complete',
                'appointments:reports',
                'reminders:view',
                'reminders:schedule',
                'reminders:cancel',
                'reminders:send-manual',
                'reminders:bulk',
                'reminders:analytics'
            ])->pluck('id')->toArray();

            $receptionistRole->permissions()->sync($receptionistPermissions);
            $this->command->info("✅ Receptionist role assigned " . count($receptionistPermissions) . " permissions");
        }

        // Assign permissions to patient role for their own data
        $patientRole = Role::where('code', 'patient')->first();
        if ($patientRole) {
            $patientPermissions = Permission::whereIn('code', [
                'patients:view-files',
                'patients:manage-files',
                'patients:view-notes',
                'patients:view-medical',
                'appointments:view-own',
                'appointments:create',
                'appointments:cancel',
                'appointments:view-slots',
                'notifications:view',
                'notifications:manage'
            ])->pluck('id')->toArray();

            $patientRole->permissions()->sync($patientPermissions);
            $this->command->info("✅ Patient role assigned " . count($patientPermissions) . " permissions");
        }
    }
}
