<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            [
                'code' => 'PD',
                'name' => 'Production',
                'description' => 'Production Department - Manufacturing and assembly operations',
                'is_active' => true,
            ],
            [
                'code' => 'QC',
                'name' => 'Quality',
                'description' => 'Quality Department - Quality control and testing',
                'is_active' => true,
            ],
            [
                'code' => 'PC',
                'name' => 'PPIC',
                'description' => 'PPIC Department - Production Planning and Inventory Control',
                'is_active' => true,
            ],
            [
                'code' => 'HR',
                'name' => 'Human Resources',
                'description' => 'HR Department - Employee management and development',
                'is_active' => true,
            ],
            [
                'code' => 'MK',
                'name' => 'Marketing',
                'description' => 'Marketing Department - Marketing and sales operations',
                'is_active' => true,
            ],
            [
                'code' => 'IT',
                'name' => 'Information Technology',
                'description' => 'IT Department - System administration and support',
                'is_active' => true,
            ],
            [
                'code' => 'PS',
                'name' => 'Purchasing',
                'description' => 'Purchasing Department - Procurement and supplier management',
                'is_active' => true,
            ],
            [
                'code' => 'MN',
                'name' => 'Maintenance',
                'description' => 'Maintenance Department - Equipment and facility maintenance',
                'is_active' => true,
            ],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate(
                ['code' => $department['code']],
                $department
            );
        }

        $this->command->info('✓ Created ' . count($departments) . ' departments');
    }
}
