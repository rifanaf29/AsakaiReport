<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\KpiDefinition;
use App\Models\KpiTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class MnPrefillTest extends TestCase
{
    use RefreshDatabase;

    public function test_mn_prefill_accepts_downtime_and_finish_keys(): void
    {
        $department = Department::create([
            'code' => 'MN',
            'name' => 'Maintenance',
            'description' => 'Maintenance Department',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'department_id' => $department->id,
            'can_access_all_departments' => false,
        ]);

        Permission::firstOrCreate(['name' => 'create kpi', 'guard_name' => 'web']);
        $user->givePermissionTo('create kpi');

        $template = KpiTemplate::create([
            'name' => 'MN Template',
            'code' => 'TPL_MN',
            'description' => null,
            'target_unit' => '%',
            'actual_mode' => 'manual',
            'actual_aggregation' => null,
            'actual_field_keys' => [],
            'actual_formula' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $downtimeDef = KpiDefinition::create([
            'kpi_template_id' => $template->id,
            'department_id' => $department->id,
            'display_name' => 'Downtime Machine MN',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        // Ensure the controller reads MN_KPI_API_URL via env().
        putenv('MN_KPI_API_URL=https://mn-api.test/api/mn-kpi');
        $_ENV['MN_KPI_API_URL'] = 'https://mn-api.test/api/mn-kpi';

        Http::fake([
            'https://mn-api.test/api/mn-kpi*' => Http::response(['finish' => 7, 'downtime' => 12.18], 200),
        ]);

        $this->actingAs($user);

        $response = $this->getJson(route('kpi.entries.mn-prefill', [
            'date' => '2026-03-12',
            'department_id' => $department->id,
            'working_hours' => 24,
            'kpi_definition_ids' => [$downtimeDef->id],
        ]));

        $response->assertOk();

        $expectedPct = (12.18 / 24) * 100.0;
        $actual = $response->json('data.' . $downtimeDef->id . '.actual');

        $this->assertNotNull($actual);
        $this->assertEqualsWithDelta($expectedPct, (float) $actual, 0.0001);
    }
}
