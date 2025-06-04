<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;

class AppointmentPermissionSeeder extends Seeder
{
    public function run()
    {
        // Define appointment-specific permissions
        $appointmentPermissions = [
            ['name' => 'View All Appointments', 'code' => 'appointments:view-all'],
            ['name' => 'View Own Appointments', 'code' => 'appointments:view-own'],
            ['name' => 'Create Appointments', 'code' => 'appointments:create'],
            ['name' => 'Update Appointments', 'code' => 'appointments:update'],
            ['name' => 'Cancel Appointments', 'code' => 'appointments:cancel'],
            ['name' => 'Confirm Appointments', 'code' => 'appointments:confirm'],
            ['name' => 'Complete Appointments', 'code' => 'appointments:complete'],
            ['name' => 'View Available Slots', 'code' => 'appointments:view-slots'],
            ['name' => 'Manage Schedule', 'code' => 'appointments:manage-schedule'],
            ['name' => 'Block Time Slots', 'code' => 'appointments:block-slots'],
            ['name' => 'View Appointment Reports', 'code' => 'appointments:reports'],
        ];

        // Create permissions
        foreach ($appointmentPermissions as $permissionData) {
            Permission::updateOrCreate(
                ['code' => $permissionData['code']],
                [
                    'name' => $permissionData['name'],
                    'group' => 'appointments',
                    'description' => $permissionData['name'] . ' permission',
                ]
            );
        }

        // Assign permissions to roles
        $this->assignPermissionsToRoles();

        $this->command->info('Appointment permissions created and assigned successfully!');
    }

    private function assignPermissionsToRoles()
    {
        // First, retrieve all the permission objects we need
        $viewAppointments = Permission::where('code', 'appointments:view-all')->first();
        $viewOwnAppointments = Permission::where('code', 'appointments:view-own')->first();
        $createAppointments = Permission::where('code', 'appointments:create')->first();
        $editAppointments = Permission::where('code', 'appointments:update')->first();
        $cancelAppointments = Permission::where('code', 'appointments:cancel')->first();
        $confirmAppointments = Permission::where('code', 'appointments:confirm')->first();
        $completeAppointments = Permission::where('code', 'appointments:complete')->first();
        $viewSlots = Permission::where('code', 'appointments:view-slots')->first();
        $manageSchedule = Permission::where('code', 'appointments:manage-schedule')->first();
        $blockSlots = Permission::where('code', 'appointments:block-slots')->first();
        $viewReports = Permission::where('code', 'appointments:reports')->first();
        
        // Admin gets all permissions
        $adminRole = Role::where('code', 'admin')->first();
        if ($adminRole) {
            $allPermissions = Permission::where('group', 'appointments')->pluck('id');
            $adminRole->permissions()->syncWithoutDetaching($allPermissions);
        }

        // Doctor permissions
        $doctorRole = Role::where('code', 'doctor')->first();
        if ($doctorRole) {
            $doctorPermissions = Permission::whereIn('code', [
                'appointments:view-own',
                'appointments:create',
                'appointments:update',
                'appointments:cancel',
                'appointments:confirm',
                'appointments:complete',
                'appointments:view-slots',
                'appointments:manage-schedule',
                'appointments:block-slots',
            ])->pluck('id');
            
            $doctorRole->permissions()->syncWithoutDetaching($doctorPermissions);
        }

        // Patient permissions
        $patientRole = Role::where('code', 'patient')->first();
        if ($patientRole) {
            $patientPermissions = Permission::whereIn('code', [
                'appointments:view-own',
                'appointments:create',
                'appointments:cancel',
                'appointments:view-slots',
            ])->pluck('id');
            
            $patientRole->permissions()->syncWithoutDetaching($patientPermissions);
        }

        // Receptionist permissions
        $receptionistRole = Role::where('code', 'receptionist')->first();
        if ($receptionistRole && $viewAppointments && $createAppointments && $editAppointments && 
            $cancelAppointments && $confirmAppointments && $completeAppointments && $viewReports) {
            
            $receptionistRole->permissions()->syncWithoutDetaching([
                $viewAppointments->id,
                $viewReports->id,  // Added for reporting
                $createAppointments->id,
                $editAppointments->id,
                $cancelAppointments->id,
                $confirmAppointments->id,
                $completeAppointments->id,
            ]);
        }

        // Nurse permissions
        $nurseRole = Role::where('code', 'nurse')->first();
        if ($nurseRole && $viewAppointments && $createAppointments && $editAppointments && 
            $cancelAppointments && $confirmAppointments && $completeAppointments) {
            
            $nurseRole->permissions()->syncWithoutDetaching([
                $viewAppointments->id,
                $createAppointments->id,
                $editAppointments->id,
                $cancelAppointments->id,
                $confirmAppointments->id,
                $completeAppointments->id,
                // Note: No delete for nurses (optional)
            ]);
        }
    }
}