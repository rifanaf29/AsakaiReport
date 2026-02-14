<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CapaArea;
use App\Models\Department;
use App\Models\User;

class CapaAreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = Department::all();
        $adminUser = User::where('email', 'admin@example.com')->first();

        if ($departments->isEmpty()) {
            $this->command->warn('No departments found. Please run DepartmentSeeder first.');
            return;
        }

        $capaAreas = [
            // Production Department Areas
            [
                'area_name' => 'Production Line 1',
                'area_description' => 'Main production line for product assembly',
                'capa_date' => now()->subDays(30),
            ],
            [
                'area_name' => 'Production Line 2',
                'area_description' => 'Secondary production line for backup and overflow',
                'capa_date' => now()->subDays(25),
            ],
            [
                'area_name' => 'Quality Control Station',
                'area_description' => 'Quality inspection and testing area',
                'capa_date' => now()->subDays(20),
            ],
            [
                'area_name' => 'Packaging Area',
                'area_description' => 'Final product packaging and labeling',
                'capa_date' => now()->subDays(15),
            ],
            
            // Warehouse Areas
            [
                'area_name' => 'Raw Material Storage',
                'area_description' => 'Storage area for incoming raw materials',
                'capa_date' => now()->subDays(28),
            ],
            [
                'area_name' => 'Finished Goods Warehouse',
                'area_description' => 'Storage for completed products ready for shipment',
                'capa_date' => now()->subDays(22),
            ],
            [
                'area_name' => 'Shipping Dock',
                'area_description' => 'Loading and unloading area for shipments',
                'capa_date' => now()->subDays(18),
            ],
            
            // Office Areas
            [
                'area_name' => 'Administrative Office',
                'area_description' => 'Main office area for administrative staff',
                'capa_date' => now()->subDays(27),
            ],
            [
                'area_name' => 'IT Department',
                'area_description' => 'Information technology and systems management',
                'capa_date' => now()->subDays(24),
            ],
            [
                'area_name' => 'Meeting Rooms',
                'area_description' => 'Conference and meeting facilities',
                'capa_date' => now()->subDays(21),
            ],
            
            // Maintenance Areas
            [
                'area_name' => 'Maintenance Workshop',
                'area_description' => 'Equipment repair and maintenance facility',
                'capa_date' => now()->subDays(26),
            ],
            [
                'area_name' => 'Tool Room',
                'area_description' => 'Storage and management of tools and equipment',
                'capa_date' => now()->subDays(19),
            ],
        ];

        foreach ($capaAreas as $index => $areaData) {
            // Distribute areas across departments
            $department = $departments[$index % $departments->count()];

            CapaArea::create([
                'department_id' => $department->id,
                'kpi_entry_id' => null,
                'area_name' => $areaData['area_name'],
                'area_description' => $areaData['area_description'],
                'capa_date' => $areaData['capa_date'],
                'is_mandatory' => false,
                'created_by' => $adminUser?->id,
            ]);
        }

        $this->command->info('CAPA areas created successfully!');
    }
}
