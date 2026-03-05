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
            ['area_name' => 'SBB', 'department_id' => 1],
            ['area_name' => 'BOX', 'department_id' => 1],
            ['area_name' => 'DELIVERY', 'department_id' => 1],
            ['area_name' => 'SUBCONT', 'department_id' => 1],
            ['area_name' => 'OFFICE PPIC (PPC,DELCO)', 'department_id' => 1],
            ['area_name' => 'GUDANG SP', 'department_id' => 1],
            ['area_name' => 'AREA LOGISTIC', 'department_id' => 1],
            ['area_name' => 'FINISH GOOD', 'department_id' => 1],
            ['area_name' => 'PD1 CUTTING', 'department_id' => 2],
            ['area_name' => 'PD1 SUPPLY  & U-BOLT', 'department_id' => 2],
            ['area_name' => 'PD1 HEATTREATMENT & ADJUSTING', 'department_id' => 2],
            ['area_name' => 'PD2 ENGINEPART & TRANSMISSION', 'department_id' => 2],
            ['area_name' => 'PD2 MACHINING & GRINDING', 'department_id' => 2],
            ['area_name' => 'PD3 MACHINING & WELDING', 'department_id' => 2],
            ['area_name' => 'PD3 PUNCH-BENDING', 'department_id' => 2],
            ['area_name' => 'PD3 ASSEMBLING', 'department_id' => 2],
            ['area_name' => 'PD4 ENDFITTING', 'department_id' => 2],
            ['area_name' => 'PD5 HOSE BRAKE', 'department_id' => 2],
            ['area_name' => 'OFFICE PD', 'department_id' => 2],
            ['area_name' => 'OUT GOING', 'department_id' => 3],
            ['area_name' => 'IN PROSES', 'department_id' => 3],
            ['area_name' => 'OFFICE QUALITY', 'department_id' => 3],
            ['area_name' => 'INCOMING BB', 'department_id' => 3],
            ['area_name' => 'LAB QA', 'department_id' => 3],
            ['area_name' => 'FINAL INSPECTION EF', 'department_id' => 3],
            ['area_name' => 'INCOMING & FINAL INSPECTION PL', 'department_id' => 3],
            ['area_name' => 'RUANG PERBAIKAN', 'department_id' => 4],
            ['area_name' => 'RUANG KOMPRESSOR', 'department_id' => 4],
            ['area_name' => 'RUANG LVMDP', 'department_id' => 4],
            ['area_name' => 'RUANG TRAFO', 'department_id' => 4],
            ['area_name' => 'POS MAINTENANCE', 'department_id' => 4],
            ['area_name' => 'OIL STORAGE', 'department_id' => 4],
            ['area_name' => 'WORKSHOP MACHINING', 'department_id' => 4],
            ['area_name' => 'WORKSHOP KONSTRUKSI', 'department_id' => 4],
            ['area_name' => 'COOLING TOWER', 'department_id' => 4],
            ['area_name' => 'KOMPRESSOR HOSE BRAKE', 'department_id' => 4],
            ['area_name' => 'GUDANG ENGINEERING (PE)', 'department_id' => 5],
            ['area_name' => 'OFFICE ENGINEERING (PE)', 'department_id' => 5],
            ['area_name' => 'AREA JIG LT 2 (PE)', 'department_id' => 5],
            ['area_name' => 'GUDANG ENGINEERING (TE)', 'department_id' => 6],
            ['area_name' => 'OFFICE ENGINEERING (TE)', 'department_id' => 6],
            ['area_name' => 'AREA JIG LT 2 (TE)', 'department_id' => 6],
            ['area_name' => 'OFFICE PURCHASING', 'department_id' => 7],
            ['area_name' => 'ARSIP PURCHASING', 'department_id' => 7],
            ['area_name' => 'OFFICE MARKETING', 'department_id' => 8],
            ['area_name' => 'ARSIP MARKETING', 'department_id' => 8],
            ['area_name' => 'OFFICE ACCOUNTING', 'department_id' => 9],
            ['area_name' => 'ARSIP ACCOUNTING', 'department_id' => 9],
            ['area_name' => 'Office Lantai 1', 'department_id' => 10],
            ['area_name' => 'Office Lantai 2', 'department_id' => 10],
            ['area_name' => 'Office Lantai 3', 'department_id' => 10],
            ['area_name' => 'Office Lantai 4', 'department_id' => 10],
            ['area_name' => 'Ruang Training', 'department_id' => 10],
            ['area_name' => 'Pos 1, Pos 2, & Parkir Mobil', 'department_id' => 10],
            ['area_name' => 'R.Tunggu & TOILET R.Tunggu (L&P)', 'department_id' => 10],
            ['area_name' => 'Partisi 1 - 5', 'department_id' => 10],
            ['area_name' => 'Tempat Pembuangan Sampah & NG', 'department_id' => 10],
            ['area_name' => 'Toilet Manufacture (L&P) & Musholla Manufacture', 'department_id' => 10],
            ['area_name' => 'Pos 3, Kantin Belakang, & Parkir Motor', 'department_id' => 10],
            ['area_name' => 'Toilet Belakang (L&P) & Musholla Belakang', 'department_id' => 10],
            ['area_name' => 'Loker (L&P) & Masjid Sementara', 'department_id' => 10],
        ];

        foreach ($capaAreas as $index => $areaData) {
            $department = isset($areaData['department_id'])
                ? Department::find($areaData['department_id'])
                : null;
            $department = $department ?: $departments[$index % $departments->count()];

            CapaArea::create([
                'department_id' => $department->id,
                'kpi_entry_id' => null,
                'area_name' => $areaData['area_name'],
                'area_description' => $areaData['area_description'] ?? null,
                'capa_date' => $areaData['capa_date'] ?? now()->toDateString(),
                'is_mandatory' => false,
                'created_by' => $adminUser?->id,
            ]);
        }

        $this->command->info('CAPA areas created successfully!');
    }
}
