<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\KpiTemplate;

class KpiTemplateDepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = KpiTemplate::with('departments')->get();

        foreach ($templates as $template) {
            if (!$template->department_id) {
                continue;
            }

            $template->departments()->syncWithoutDetaching([
                $template->department_id => [
                    'display_name' => $template->name,
                    'is_active' => $template->is_active,
                    'sort_order' => $template->sort_order,
                ],
            ]);
        }

        $this->command->info('✓ KPI template department assignments created');
    }
}
