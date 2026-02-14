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
                'code' => 'PROD',
                'name' => 'Production',
                'description' => 'Production Department - Manufacturing and assembly operations',
                'is_active' => true,
            ],
            [
                'code' => 'QA',
                'name' => 'Quality Assurance',
                'description' => 'Quality Assurance Department - Quality control and testing',
                'is_active' => true,
            ],
            [
                'code' => 'LOG',
                'name' => 'Logistics',
                'description' => 'Logistics Department - Warehouse and shipping operations',
                'is_active' => true,
            ],
            [
                'code' => 'HR',
                'name' => 'Human Resources',
                'description' => 'HR Department - Employee management and development',
                'is_active' => true,
            ],
            [
                'code' => 'FIN',
                'name' => 'Finance',
                'description' => 'Finance Department - Financial planning and accounting',
                'is_active' => true,
            ],
            [
                'code' => 'IT',
                'name' => 'Information Technology',
                'description' => 'IT Department - System administration and support',
                'is_active' => true,
            ],
            [
                'code' => 'ENG',
                'name' => 'Engineering',
                'description' => 'Engineering Department - Product design and development',
                'is_active' => true,
            ],
            [
                'code' => 'MAINT',
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
