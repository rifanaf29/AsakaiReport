<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure departments exist
        $departments = Department::all();
        
        if ($departments->isEmpty()) {
            $this->command->error('No departments found! Please run DepartmentSeeder first.');
            return;
        }

        // Get specific departments
        $prodDept = Department::where('code', 'PROD')->first();
        $qaDept = Department::where('code', 'QA')->first();
        $logDept = Department::where('code', 'LOG')->first();
        $itDept = Department::where('code', 'IT')->first();
        
        // Fallback to first department if specific ones don't exist
        $defaultDept = $prodDept ?? $departments->first();

        // ========================================
        // ADMIN USERS (Can access all departments)
        // ========================================
        
        $this->command->info('Creating Admin users...');
        
        $admin = User::firstOrCreate(
            ['email' => 'admin@asakai.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('Admin@123'),
                'department_id' => $itDept?->id ?? $defaultDept->id,
                'can_access_all_departments' => true,
                'email_verified_at' => now(),
            ]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
        $this->command->info('  ✓ Admin: admin@asakai.com / Admin@123 (IT Department)');

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@asakai.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('Super@123'),
                'department_id' => $itDept?->id ?? $defaultDept->id,
                'can_access_all_departments' => true,
                'email_verified_at' => now(),
            ]
        );
        if (!$superAdmin->hasRole('admin')) {
            $superAdmin->assignRole('admin');
        }
        $this->command->info('  ✓ Super Admin: superadmin@asakai.com / Super@123 (IT Department)');

        // ========================================
        // MANAGER USERS (One per department)
        // ========================================
        
        $this->command->info('Creating Manager users...');
        
        $managers = [
            [
                'name' => 'Production Manager',
                'email' => 'manager.prod@asakai.com',
                'department' => $prodDept ?? $defaultDept,
            ],
            [
                'name' => 'QA Manager',
                'email' => 'manager.qa@asakai.com',
                'department' => $qaDept ?? $defaultDept,
            ],
            [
                'name' => 'Logistics Manager',
                'email' => 'manager.log@asakai.com',
                'department' => $logDept ?? $defaultDept,
            ],
        ];

        foreach ($managers as $managerData) {
            $manager = User::firstOrCreate(
                ['email' => $managerData['email']],
                [
                    'name' => $managerData['name'],
                    'password' => Hash::make('Manager@123'),
                    'department_id' => $managerData['department']->id,
                    'can_access_all_departments' => false,
                    'email_verified_at' => now(),
                ]
            );
            if (!$manager->hasRole('manager')) {
                $manager->assignRole('manager');
            }
            $this->command->info('  ✓ Manager: ' . $managerData['email'] . ' / Manager@123 (' . $managerData['department']->name . ')');
        }

        // ========================================
        // REGULAR USERS (Multiple per department)
        // ========================================
        
        $this->command->info('Creating Regular users...');
        
        $users = [
            // Production Department Users
            [
                'name' => 'John Doe',
                'email' => 'john.doe@asakai.com',
                'department' => $prodDept ?? $defaultDept,
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane.smith@asakai.com',
                'department' => $prodDept ?? $defaultDept,
            ],
            // QA Department Users
            [
                'name' => 'Mike Johnson',
                'email' => 'mike.johnson@asakai.com',
                'department' => $qaDept ?? $defaultDept,
            ],
            [
                'name' => 'Sarah Williams',
                'email' => 'sarah.williams@asakai.com',
                'department' => $qaDept ?? $defaultDept,
            ],
            // Logistics Department Users
            [
                'name' => 'David Brown',
                'email' => 'david.brown@asakai.com',
                'department' => $logDept ?? $defaultDept,
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily.davis@asakai.com',
                'department' => $logDept ?? $defaultDept,
            ],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make('User@123'),
                    'department_id' => $userData['department']->id,
                    'can_access_all_departments' => false,
                    'email_verified_at' => now(),
                ]
            );
            if (!$user->hasRole('user')) {
                $user->assignRole('user');
            }
            $this->command->info('  ✓ User: ' . $userData['email'] . ' / User@123 (' . $userData['department']->name . ')');
        }

        // ========================================
        // SUMMARY
        // ========================================
        
        $this->command->info('');
        $this->command->info('========================================');
        $this->command->info('User Creation Summary');
        $this->command->info('========================================');
        $this->command->info('Total Users Created: ' . User::count());
        $this->command->info('');
        $this->command->info('Login Credentials:');
        $this->command->info('----------------------------------------');
        $this->command->info('ADMIN ACCOUNTS (Full Access):');
        $this->command->info('  • admin@asakai.com / Admin@123');
        $this->command->info('  • superadmin@asakai.com / Super@123');
        $this->command->info('');
        $this->command->info('MANAGER ACCOUNTS (Department Level):');
        $this->command->info('  • manager.prod@asakai.com / Manager@123');
        $this->command->info('  • manager.qa@asakai.com / Manager@123');
        $this->command->info('  • manager.log@asakai.com / Manager@123');
        $this->command->info('');
        $this->command->info('USER ACCOUNTS (Basic Access):');
        $this->command->info('  • john.doe@asakai.com / User@123');
        $this->command->info('  • jane.smith@asakai.com / User@123');
        $this->command->info('  • mike.johnson@asakai.com / User@123');
        $this->command->info('  • sarah.williams@asakai.com / User@123');
        $this->command->info('  • david.brown@asakai.com / User@123');
        $this->command->info('  • emily.davis@asakai.com / User@123');
        $this->command->info('========================================');
    }
}
