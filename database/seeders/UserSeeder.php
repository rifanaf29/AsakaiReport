<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $domain = 'garudametalutama.com';
        $defaultPassword = 'Kitabisa23';

        // Ensure departments exist
        $departments = Department::all();
        
        if ($departments->isEmpty()) {
            $this->command->error('No departments found! Please run DepartmentSeeder first.');
            return;
        }

        // Get specific departments
        $prodDept = Department::where('code', 'PD')->first();
        $qaDept = Department::where('code', 'QC')->first();
        $logDept = Department::where('code', 'PC')->first();
        $itDept = Department::where('code', 'IT')->first();
        
        // Fallback to first department if specific ones don't exist
        $defaultDept = $prodDept ?? $departments->first();

        $toDepartmentEmailLocal = function (?Department $department): string {
            $name = $department?->name ?: 'department';
            $base = strtolower((string) $name);
            // Keep only [a-z0-9] to avoid email local-part edge cases.
            $sanitized = preg_replace('/[^a-z0-9]+/', '', $base) ?: 'department';
            return $sanitized;
        };

        $makeUniqueEmail = function (string $local, array $used, ?Department $department = null): string {
            $candidate = $local;
            if (in_array($candidate, $used, true)) {
                $suffix = $department?->code ?: ($department?->id ? ('dept' . $department->id) : Str::random(4));
                $candidate = $candidate . $suffix;
            }
            return $candidate;
        };

        $toFirstNameEmailLocal = function (string $name): string {
            $trimmed = trim($name);
            $parts = preg_split('/\s+/', $trimmed);
            $first = $parts && $parts[0] ? $parts[0] : $trimmed;
            $first = strtolower((string) $first);
            $sanitized = preg_replace('/[^a-z0-9]+/i', '', (string) $first);
            return $sanitized ?: 'user';
        };

        $findDepartmentByHint = function (string $hint) use ($departments, $defaultDept, $prodDept, $qaDept, $logDept): Department {
            $needle = strtolower(trim($hint));

            $match = $departments->first(function ($department) use ($needle) {
                $name = strtolower((string) ($department->name ?? ''));
                $code = strtolower((string) ($department->code ?? ''));
                return $needle !== '' && (Str::contains($name, $needle) || Str::contains($code, $needle));
            });
            if ($match) return $match;

            // Common fallbacks if department naming differs.
            if (Str::contains($needle, 'produksi') && $prodDept) return $prodDept;
            if (Str::contains($needle, 'quality') && $qaDept) return $qaDept;
            if (Str::contains($needle, 'qos') && $qaDept) return $qaDept;
            if (Str::contains($needle, 'log') && $logDept) return $logDept;

            return $defaultDept;
        };

        // ========================================
        // ADMIN USERS (Can access all departments)
        // ========================================
        
        $this->command->info('Creating Admin users...');
        
        $admin = User::firstOrCreate(
            ['email' => 'admin@' . $domain],
            [
                'name' => 'System Administrator',
                'password' => Hash::make($defaultPassword),
                'department_id' => $itDept?->id ?? $defaultDept->id,
                'can_access_all_departments' => true,
                'email_verified_at' => now(),
            ]
        );
        if (!$admin->hasRole('admin')) {
            $admin->assignRole('admin');
        }
        $this->command->info('  ✓ Admin: admin@' . $domain . ' / ' . $defaultPassword . ' (IT Department)');

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@' . $domain],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make($defaultPassword),
                'department_id' => $itDept?->id ?? $defaultDept->id,
                'can_access_all_departments' => true,
                'email_verified_at' => now(),
            ]
        );
        if (!$superAdmin->hasRole('admin')) {
            $superAdmin->assignRole('admin');
        }
        $this->command->info('  ✓ Super Admin: superadmin@' . $domain . ' / ' . $defaultPassword . ' (IT Department)');

        // ========================================
        // MANAGER USERS (Full access)
        // ========================================
        
        $this->command->info('Creating Manager users...');

        $usedEmails = [
            'admin@' . $domain,
            'superadmin@' . $domain,
        ];
        
        $managers = [
            ['name' => 'Hanung S. Talogo', 'title' => 'Direktur', 'department_hint' => 'IT'],
            ['name' => 'Ade Chandra', 'title' => 'General Manager', 'department_hint' => 'IT'],
            ['name' => 'Usman Khadir', 'title' => 'Manager Maintenance', 'department_hint' => 'maintenance'],
            ['name' => 'Ardian Telaumbauna', 'title' => 'Manager Produksi', 'department_hint' => 'produksi'],
            ['name' => 'David Imansyah', 'title' => 'Manager Marketing', 'department_hint' => 'marketing'],
            ['name' => 'Ferdi', 'title' => 'Manager Marketing', 'department_hint' => 'marketing'],
            ['name' => 'Gede Sudarmawan', 'title' => 'Manager PPIC', 'department_hint' => 'ppic'],
            ['name' => 'Ricky Tjahjana', 'title' => 'Manager Purchasing', 'department_hint' => 'purchasing'],
            ['name' => 'Saroto', 'title' => 'Manager Engineering', 'department_hint' => 'engineering'],
            ['name' => 'Catur Rianto', 'title' => 'Manager Engineering', 'department_hint' => 'engineering'],
            ['name' => 'Martin', 'title' => 'Manager Accounting', 'department_hint' => 'accounting'],
            ['name' => 'Nur Hidayat', 'title' => 'Manager QoS', 'department_hint' => 'qos'],
            ['name' => 'Juar Syah Putra', 'title' => 'Manager Quality', 'department_hint' => 'quality'],
        ];

        foreach ($managers as $managerData) {
            $department = $findDepartmentByHint($managerData['department_hint'] ?? '');

            $local = $toFirstNameEmailLocal($managerData['name']);
            $local = $makeUniqueEmail($local, $usedEmails, $department);
            $email = $local . '@' . $domain;
            $usedEmails[] = $email;

            $manager = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => trim($managerData['name'] . ' (' . ($managerData['title'] ?? 'Manager') . ')'),
                    'password' => Hash::make($defaultPassword),
                    'department_id' => $department->id,
                    'can_access_all_departments' => true,
                    'email_verified_at' => now(),
                ]
            );
            if (!$manager->hasRole('manager')) {
                $manager->assignRole('manager');
            }
            $this->command->info('  ✓ Manager: ' . $email . ' / ' . $defaultPassword . ' (' . $department->name . ')');
        }

        // ========================================
        // REGULAR USERS (Multiple per department)
        // ========================================
        
        $this->command->info('Creating Regular users...');

        // One "user" account per department using department name as email local part.
        foreach ($departments as $department) {
            $local = $toDepartmentEmailLocal($department);
            $local = $makeUniqueEmail($local, $usedEmails, $department);
            $email = $local . '@' . $domain;
            $usedEmails[] = $email;

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $department->name . ' User',
                    'password' => Hash::make($defaultPassword),
                    'department_id' => $department->id,
                    'can_access_all_departments' => false,
                    'email_verified_at' => now(),
                ]
            );
            if (!$user->hasRole('user')) {
                $user->assignRole('user');
            }
            $this->command->info('  ✓ User: ' . $email . ' / ' . $defaultPassword . ' (' . $department->name . ')');
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
        $this->command->info('  • admin@' . $domain . ' / ' . $defaultPassword);
        $this->command->info('  • superadmin@' . $domain . ' / ' . $defaultPassword);
        $this->command->info('');
        $this->command->info('MANAGER ACCOUNTS (Full Access):');
        $this->command->info('  • {name}@' . $domain . ' / ' . $defaultPassword . ' (13 managers)');
        $this->command->info('');
        $this->command->info('USER ACCOUNTS (Basic Access):');
        $this->command->info('  • {department}@' . $domain . ' / ' . $defaultPassword . ' (1 user per department)');
        $this->command->info('========================================');
    }
}