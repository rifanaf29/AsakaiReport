<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Department permissions
            'view departments',
            'create departments',
            'edit departments',
            'delete departments',

            // KPI Template permissions
            'view kpi templates',
            'create kpi templates',
            'edit kpi templates',
            'delete kpi templates',

            // KPI Entry permissions
            'view kpi',
            'create kpi',
            'edit kpi',
            'delete kpi',
            'lock kpi',
            'unlock kpi',
            'view all department kpi', // Admin only

            // CAPA permissions
            'view capa',
            'create capa',
            'edit capa',
            'delete capa',
            'assign capa',
            'close capa',
            'view all department capa', // Admin only

            // User management
            'view users',
            'create users',
            'edit users',
            'delete users',
            'assign roles',

            // Reports
            'view reports',
            'export reports',
            'view all department reports', // Admin only

            // System settings
            'manage settings',
            'view audit logs',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions

        // 1. ADMIN - Full access to everything
        $admin = Role::create(['name' => 'admin']);
        $admin->givePermissionTo(Permission::all());

        // 2. MANAGER - Department-level management
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo([
            // Department (own department only)
            'view departments',

            // KPI Templates (can manage templates)
            'view kpi templates',
            'create kpi templates',
            'edit kpi templates',
            'delete kpi templates',

            // KPI (full access within department)
            'view kpi',
            'create kpi',
            'edit kpi',
            'delete kpi',
            'lock kpi',

            // CAPA (full access within department)
            'view capa',
            'create capa',
            'edit capa',
            'delete capa',
            'assign capa',
            'close capa',

            // Users (view only within department)
            'view users',
            'create users',
            'edit users',

            // Reports (own department)
            'view reports',
            'export reports',
        ]);

        // 3. USER - Basic access
        $user = Role::create(['name' => 'user']);
        $user->givePermissionTo([
            // Department (view only)
            'view departments',

            // KPI Templates (view only)
            'view kpi templates',

            // KPI (create and edit own entries)
            'view kpi',
            'create kpi',
            'edit kpi',

            // CAPA (create and edit own entries)
            'view capa',
            'create capa',
            'edit capa',

            // Reports (view only)
            'view reports',
        ]);

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Created roles: admin, manager, user');
        $this->command->info('Created ' . count($permissions) . ' permissions');
    }
}
