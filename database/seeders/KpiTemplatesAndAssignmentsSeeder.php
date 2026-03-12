<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;
use App\Models\KpiDefinition;
use App\Models\KpiMonthlyTarget;
use App\Models\KpiTemplate;
use Carbon\Carbon;

class KpiTemplatesAndAssignmentsSeeder extends Seeder
{
    public function run(): void
    {
        $year = 2026;
        $month = 1; // stored as yearly target (January)

        $departments = Department::query()->get()->keyBy('code');

        $requireDept = function (string $code) use ($departments): Department {
            $dept = $departments->get($code);
            if (!$dept) {
                throw new \RuntimeException("Department with code '{$code}' not found. Run DepartmentSeeder first.");
            }
            return $dept;
        };

        $templates = [];

        // 1) Template Normal (Target + Actual only)
        $templates['TPL_NORMAL'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_NORMAL'],
            [
                'name' => 'Template Normal',
                'description' => 'Target + Actual only (no additional fields).',
                'target_unit' => '%',
                'actual_mode' => 'manual',
                'actual_aggregation' => null,
                'actual_field_keys' => null,
                'actual_formula' => null,
                'is_active' => true,
                'sort_order' => 10,
            ]
        );

        // 2) Special Template (Actual = average of PD1..PD5)
        $templates['TPL_SPECIAL_PD1_PD5_AVG'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_SPECIAL_PD1_PD5_AVG'],
            [
                'name' => 'Special Template (Avg PD1-PD5)',
                'description' => 'Target + Actual. Actual is average of PD1..PD5.',
                'target_unit' => '%',
                'actual_mode' => 'aggregated',
                'actual_aggregation' => 'avg',
                'actual_field_keys' => ['pd1', 'pd2', 'pd3', 'pd4', 'pd5'],
                'actual_formula' => null,
                'is_active' => true,
                'sort_order' => 20,
            ]
        );

        foreach (['pd1', 'pd2', 'pd3', 'pd4', 'pd5'] as $idx => $key) {
            $templates['TPL_SPECIAL_PD1_PD5_AVG']->fields()->updateOrCreate(
                ['field_key' => $key],
                [
                    'field_name' => strtoupper($key),
                    'field_type' => 'decimal',
                    'is_required' => false,
                    'is_editable' => true,
                    'calculation_formula' => null,
                    'unit' => null, // overridden per KPI definition (department-specific)
                    'sort_order' => ($idx + 1) * 10,
                ]
            );
        }

        // 3) PPIC Special Template: Shortage (Order/Shortage as additional fields)
        $templates['TPL_PPIC_SHORTAGE'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_PPIC_SHORTAGE'],
            [
                'name' => 'PPIC Special Template (Shortage)',
                'description' => 'Target/Actual in PPM + additional fields Order(Pcs) and Shortage(Pcs).',
                'target_unit' => 'ppm',
                'actual_mode' => 'manual',
                'actual_aggregation' => null,
                'actual_field_keys' => null,
                'actual_formula' => null,
                'is_active' => true,
                'sort_order' => 30,
            ]
        );

        $templates['TPL_PPIC_SHORTAGE']->fields()->updateOrCreate(
            ['field_key' => 'order_pcs'],
            [
                'field_name' => 'Order (Pcs)',
                'field_type' => 'number',
                'is_required' => false,
                'is_editable' => true,
                'calculation_formula' => null,
                'unit' => 'Pcs',
                'sort_order' => 10,
            ]
        );
        $templates['TPL_PPIC_SHORTAGE']->fields()->updateOrCreate(
            ['field_key' => 'shortage_pcs'],
            [
                'field_name' => 'Shortage (Pcs)',
                'field_type' => 'number',
                'is_required' => false,
                'is_editable' => true,
                'calculation_formula' => null,
                'unit' => 'Pcs',
                'sort_order' => 20,
            ]
        );

        // 4) PD Special Template: Rejection in Proses (formula ppm)
        $templates['TPL_PD_REJECTION_PROSES'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_PD_REJECTION_PROSES'],
            [
                'name' => 'PD Special Template (Rejection in Proses)',
                'description' => 'Actual (ppm) computed by formula: (Actual NG / Actual Produksi) * 1,000,000.',
                'target_unit' => 'ppm',
                'actual_mode' => 'aggregated',
                'actual_aggregation' => 'formula',
                'actual_field_keys' => null,
                'actual_formula' => '=(actual_ng/actual_produksi)*1000000',
                'is_active' => true,
                'sort_order' => 40,
            ]
        );

        $templates['TPL_PD_REJECTION_PROSES']->fields()->updateOrCreate(
            ['field_key' => 'actual_ng'],
            [
                'field_name' => 'Actual NG (Pcs)',
                'field_type' => 'number',
                'is_required' => false,
                'is_editable' => true,
                'calculation_formula' => null,
                'unit' => 'Pcs',
                'sort_order' => 10,
            ]
        );
        $templates['TPL_PD_REJECTION_PROSES']->fields()->updateOrCreate(
            ['field_key' => 'actual_produksi'],
            [
                'field_name' => 'Actual Produksi (Pcs)',
                'field_type' => 'number',
                'is_required' => false,
                'is_editable' => true,
                'calculation_formula' => null,
                'unit' => 'Pcs',
                'sort_order' => 20,
            ]
        );

        // 5) HR Waste templates (Actual = sum of selected additional fields)
        $templates['TPL_HR_WASTE_GRAM'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_HR_WASTE_GRAM'],
            [
                'name' => 'HR Special Template (Waste Gram)',
                'description' => 'Actual is sum of PD1..PD4 + Workshop (kg).',
                'target_unit' => 'kg',
                'actual_mode' => 'aggregated',
                'actual_aggregation' => 'sum',
                'actual_field_keys' => ['pd1', 'pd2', 'pd3', 'pd4', 'workshop'],
                'actual_formula' => null,
                'is_active' => true,
                'sort_order' => 50,
            ]
        );

        foreach ([
            ['key' => 'pd1', 'name' => 'PD1 (kg)'],
            ['key' => 'pd2', 'name' => 'PD2 (kg)'],
            ['key' => 'pd3', 'name' => 'PD3 (kg)'],
            ['key' => 'pd4', 'name' => 'PD4 (kg)'],
            ['key' => 'workshop', 'name' => 'Workshop (kg)'],
            ['key' => 'hasil_produksi', 'name' => 'Hasil Produksi'],
        ] as $idx => $f) {
            $templates['TPL_HR_WASTE_GRAM']->fields()->updateOrCreate(
                ['field_key' => $f['key']],
                [
                    'field_name' => $f['name'],
                    'field_type' => 'decimal',
                    'is_required' => false,
                    'is_editable' => true,
                    'calculation_formula' => null,
                    'unit' => 'kg',
                    'sort_order' => ($idx + 1) * 10,
                ]
            );
        }

        $templates['TPL_HR_WASTE_NG'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_HR_WASTE_NG'],
            [
                'name' => 'HR Special Template (Waste NG)',
                'description' => 'Actual is sum of PD1..PD4 + QA + EG (kg).',
                'target_unit' => 'kg',
                'actual_mode' => 'aggregated',
                'actual_aggregation' => 'sum',
                'actual_field_keys' => ['pd1', 'pd2', 'pd3', 'pd4', 'qa', 'eg'],
                'actual_formula' => null,
                'is_active' => true,
                'sort_order' => 60,
            ]
        );

        foreach ([
            ['key' => 'pd1', 'name' => 'PD1 (kg)'],
            ['key' => 'pd2', 'name' => 'PD2 (kg)'],
            ['key' => 'pd3', 'name' => 'PD3 (kg)'],
            ['key' => 'pd4', 'name' => 'PD4 (kg)'],
            ['key' => 'qa', 'name' => 'QA (kg)'],
            ['key' => 'eg', 'name' => 'EG (kg)'],
            ['key' => 'hasil_produksi', 'name' => 'Hasil Produksi'],
        ] as $idx => $f) {
            $templates['TPL_HR_WASTE_NG']->fields()->updateOrCreate(
                ['field_key' => $f['key']],
                [
                    'field_name' => $f['name'],
                    'field_type' => 'decimal',
                    'is_required' => false,
                    'is_editable' => true,
                    'calculation_formula' => null,
                    'unit' => 'kg',
                    'sort_order' => ($idx + 1) * 10,
                ]
            );
        }

        $templates['TPL_HR_WASTE_PUNTUNGAN'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_HR_WASTE_PUNTUNGAN'],
            [
                'name' => 'HR Special Template (Waste Puntungan)',
                'description' => 'Actual is sum of PD1 + PD3 (kg).',
                'target_unit' => 'kg',
                'actual_mode' => 'aggregated',
                'actual_aggregation' => 'sum',
                'actual_field_keys' => ['pd1', 'pd3'],
                'actual_formula' => null,
                'is_active' => true,
                'sort_order' => 70,
            ]
        );

        foreach ([
            ['key' => 'pd1', 'name' => 'PD1 (kg)'],
            ['key' => 'pd3', 'name' => 'PD3 (kg)'],
            ['key' => 'hasil_produksi', 'name' => 'Hasil Produksi'],
        ] as $idx => $f) {
            $templates['TPL_HR_WASTE_PUNTUNGAN']->fields()->updateOrCreate(
                ['field_key' => $f['key']],
                [
                    'field_name' => $f['name'],
                    'field_type' => 'decimal',
                    'is_required' => false,
                    'is_editable' => true,
                    'calculation_formula' => null,
                    'unit' => 'kg',
                    'sort_order' => ($idx + 1) * 10,
                ]
            );
        }

        // 6) HR Main KPI (sum of accidents)
        $templates['TPL_HR_ACCIDENT_MAIN'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_HR_ACCIDENT_MAIN'],
            [
                'name' => 'HR Special Template (Main KPI Accident)',
                'description' => 'Actual is sum of Fatal Accident + LWD Accident + First Aid.',
                'target_unit' => 'case',
                'actual_mode' => 'aggregated',
                'actual_aggregation' => 'sum',
                'actual_field_keys' => ['fatal_accident_a', 'lwd_accident_b', 'first_aid_c'],
                'actual_formula' => null,
                'is_active' => true,
                'sort_order' => 80,
            ]
        );

        // 6b) PD Special Template: MP & OT
        // Actual (%) computed by formula: (Sales Amount / Target Sales) * 100
        // Stores Total OT Charge (sum PD1..PD5) for table visibility.
        $templates['TPL_PD_MP_OT'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_PD_MP_OT'],
            [
                'name' => 'PD Special Template (MP & OT)',
                'description' => 'MP & OT daily form. Actual (%) computed as achievement: (sales_amount/target_sales)*100. Stores Total OT Charge (Rp).',
                'target_unit' => '%',
                'actual_mode' => 'aggregated',
                'actual_aggregation' => 'formula',
                'actual_field_keys' => null,
                'actual_formula' => '=(sales_amount/target_sales)*100',
                'is_active' => true,
                'sort_order' => 75,
            ]
        );

        foreach ([
            ['key' => 'jumlah_mp', 'name' => 'Jumlah MP', 'type' => 'number', 'unit' => 'Orang'],
            ['key' => 'jam_kerja_normal', 'name' => 'Jam Kerja Normal', 'type' => 'decimal', 'unit' => 'Jam'],
            ['key' => 'jam_ot', 'name' => 'Jam OT', 'type' => 'decimal', 'unit' => 'Jam'],

            ['key' => 'ot_charge_pd1', 'name' => 'OT Charge PD1', 'type' => 'accounting', 'unit' => 'Rp'],
            ['key' => 'ot_charge_pd2', 'name' => 'OT Charge PD2', 'type' => 'accounting', 'unit' => 'Rp'],
            ['key' => 'ot_charge_pd3', 'name' => 'OT Charge PD3', 'type' => 'accounting', 'unit' => 'Rp'],
            ['key' => 'ot_charge_pd4', 'name' => 'OT Charge PD4', 'type' => 'accounting', 'unit' => 'Rp'],
            ['key' => 'ot_charge_pd5', 'name' => 'OT Charge PD5', 'type' => 'accounting', 'unit' => 'Rp'],
            ['key' => 'total_ot_charge', 'name' => 'Total OT Charge', 'type' => 'calculated', 'unit' => 'Rp', 'formula' => '=(ot_charge_pd1+ot_charge_pd2+ot_charge_pd3+ot_charge_pd4+ot_charge_pd5)'],

            ['key' => 'sales_amount', 'name' => 'Sales Amount', 'type' => 'accounting', 'unit' => 'Rp'],
            ['key' => 'target_sales', 'name' => 'Target Sales', 'type' => 'accounting', 'unit' => 'Rp'],
            ['key' => 'achievement_pct', 'name' => 'Achievement', 'type' => 'calculated', 'unit' => '%', 'formula' => '=(sales_amount/target_sales)*100'],
        ] as $idx => $f) {
            $templates['TPL_PD_MP_OT']->fields()->updateOrCreate(
                ['field_key' => $f['key']],
                [
                    'field_name' => $f['name'],
                    'field_type' => $f['type'],
                    'is_required' => false,
                    'is_editable' => $f['type'] !== 'calculated',
                    'calculation_formula' => $f['formula'] ?? null,
                    'unit' => $f['unit'],
                    'sort_order' => ($idx + 1) * 10,
                ]
            );
        }

        // 6) PD Special Template: Waste CNC Bending
        // Actual (%) computed by formula: ((CB1+CB2+CB3+CB4)/Hasil Produksi) * 100
        $templates['TPL_PD_WASTE_CNC_BENDING'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_PD_WASTE_CNC_BENDING'],
            [
                'name' => 'PD Special Template (Waste CNC Bending)',
                'description' => 'Daily CNC waste. Actual (%) computed from CB1..CB4 vs Hasil Produksi. Stores Total Waste (KG) for dashboard.',
                'target_unit' => '%',
                'actual_mode' => 'aggregated',
                'actual_aggregation' => 'formula',
                'actual_field_keys' => null,
                'actual_formula' => '=((cb1+cb2+cb3+cb4)/hasil_produksi)*100',
                'is_active' => true,
                'sort_order' => 70,
            ]
        );

        foreach ([
            ['key' => 'hasil_produksi', 'name' => 'Hasil Produksi (KG)', 'type' => 'decimal', 'unit' => 'kg', 'required' => true],
            ['key' => 'cb1', 'name' => 'CB 1 (KG)', 'type' => 'decimal', 'unit' => 'kg', 'required' => true],
            ['key' => 'cb2', 'name' => 'CB 2 (KG)', 'type' => 'decimal', 'unit' => 'kg', 'required' => true],
            ['key' => 'cb3', 'name' => 'CB 3 (KG)', 'type' => 'decimal', 'unit' => 'kg', 'required' => true],
            ['key' => 'cb4', 'name' => 'CB 4 (KG)', 'type' => 'decimal', 'unit' => 'kg', 'required' => true],

            // Money-related rows (optional input)
            ['key' => 'd6', 'name' => 'D6 (Rp)', 'type' => 'accounting', 'unit' => 'Rp', 'required' => false],
            ['key' => 'd7', 'name' => 'D7 (Rp)', 'type' => 'accounting', 'unit' => 'Rp', 'required' => false],
            ['key' => 'd8', 'name' => 'D8 (Rp)', 'type' => 'accounting', 'unit' => 'Rp', 'required' => false],
            ['key' => 'd9', 'name' => 'D9 (Rp)', 'type' => 'accounting', 'unit' => 'Rp', 'required' => false],
            ['key' => 'd11', 'name' => 'D11 (Rp)', 'type' => 'accounting', 'unit' => 'Rp', 'required' => false],
            ['key' => 'd12', 'name' => 'D12 (Rp)', 'type' => 'accounting', 'unit' => 'Rp', 'required' => false],
            ['key' => 'd13', 'name' => 'D13 (Rp)', 'type' => 'accounting', 'unit' => 'Rp', 'required' => false],

            // Calculated (stored by controller, used for dashboard series)
            ['key' => 'total_waste_kg', 'name' => 'Total Waste (KG)', 'type' => 'calculated', 'unit' => 'kg', 'required' => false],
            ['key' => 'copq_material', 'name' => 'COPQ Material (Rp)', 'type' => 'calculated', 'unit' => 'Rp', 'required' => false],
        ] as $idx => $f) {
            $templates['TPL_PD_WASTE_CNC_BENDING']->fields()->updateOrCreate(
                ['field_key' => $f['key']],
                [
                    'field_name' => $f['name'],
                    'field_type' => $f['type'],
                    'is_required' => (bool) $f['required'],
                    'is_editable' => $f['type'] !== 'calculated',
                    'calculation_formula' => null,
                    'unit' => $f['unit'],
                    'sort_order' => ($idx + 1) * 10,
                ]
            );
        }

        foreach ([
            ['key' => 'fatal_accident_a', 'name' => 'Fatal Accident (Rank A)'],
            ['key' => 'lwd_accident_b', 'name' => 'LWD Accident (Rank B)'],
            ['key' => 'first_aid_c', 'name' => 'First Aid (Rank C)'],
        ] as $idx => $f) {
            $templates['TPL_HR_ACCIDENT_MAIN']->fields()->updateOrCreate(
                ['field_key' => $f['key']],
                [
                    'field_name' => $f['name'],
                    'field_type' => 'number',
                    'is_required' => false,
                    'is_editable' => true,
                    'calculation_formula' => null,
                    'unit' => 'case',
                    'sort_order' => ($idx + 1) * 10,
                ]
            );
        }

        // 7) QC Rejection templates (ppm formulas)
        $templates['TPL_QC_REJECTION_INCOMING'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_QC_REJECTION_INCOMING'],
            [
                'name' => 'QC Special Template (Rejection Incoming)',
                'description' => 'Actual (ppm) computed by formula: (Actual NG / Actual Kedatangan) * 1,000,000.',
                'target_unit' => 'ppm',
                'actual_mode' => 'aggregated',
                'actual_aggregation' => 'formula',
                'actual_field_keys' => null,
                'actual_formula' => '=(actual_ng/actual_kedatangan)*1000000',
                'is_active' => true,
                'sort_order' => 90,
            ]
        );

        foreach ([
            ['key' => 'actual_kedatangan', 'name' => 'Actual Kedatangan (pcs)', 'unit' => 'pcs'],
            ['key' => 'actual_ng', 'name' => 'Actual NG (pcs)', 'unit' => 'pcs'],
            ['key' => 'total_supp', 'name' => 'Total Supp', 'unit' => null],
        ] as $idx => $f) {
            $templates['TPL_QC_REJECTION_INCOMING']->fields()->updateOrCreate(
                ['field_key' => $f['key']],
                [
                    'field_name' => $f['name'],
                    'field_type' => 'number',
                    'is_required' => false,
                    'is_editable' => true,
                    'calculation_formula' => null,
                    'unit' => $f['unit'],
                    'sort_order' => ($idx + 1) * 10,
                ]
            );
        }

        $templates['TPL_QC_REJECTION_FI_NC'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_QC_REJECTION_FI_NC'],
            [
                'name' => 'QC Special Template (Rejection FI_NC)',
                'description' => 'Actual NC (ppm) computed by formula: (Actual NC / Actual Pengecekan) * 1,000,000.',
                'target_unit' => 'ppm',
                'actual_mode' => 'aggregated',
                'actual_aggregation' => 'formula',
                'actual_field_keys' => null,
                'actual_formula' => '=(actual_nc/actual_pengecekan)*1000000',
                'is_active' => true,
                'sort_order' => 100,
            ]
        );

        foreach ([
            ['key' => 'actual_pengecekan', 'name' => 'Actual Pengecekan (pcs)'],
            ['key' => 'actual_nc', 'name' => 'Actual NC (pcs)'],
        ] as $idx => $f) {
            $templates['TPL_QC_REJECTION_FI_NC']->fields()->updateOrCreate(
                ['field_key' => $f['key']],
                [
                    'field_name' => $f['name'],
                    'field_type' => 'number',
                    'is_required' => false,
                    'is_editable' => true,
                    'calculation_formula' => null,
                    'unit' => 'pcs',
                    'sort_order' => ($idx + 1) * 10,
                ]
            );
        }

        $templates['TPL_QC_REJECTION_FI_NG'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_QC_REJECTION_FI_NG'],
            [
                'name' => 'QC Special Template (Rejection FI_NG)',
                'description' => 'Actual NG (ppm) computed by formula: (Actual NG / Actual Pengecekan) * 1,000,000.',
                'target_unit' => 'ppm',
                'actual_mode' => 'aggregated',
                'actual_aggregation' => 'formula',
                'actual_field_keys' => null,
                'actual_formula' => '=(actual_ng/actual_pengecekan)*1000000',
                'is_active' => true,
                'sort_order' => 110,
            ]
        );

        foreach ([
            ['key' => 'actual_pengecekan', 'name' => 'Actual Pengecekan (pcs)'],
            ['key' => 'actual_ng', 'name' => 'Actual NG (pcs)'],
        ] as $idx => $f) {
            $templates['TPL_QC_REJECTION_FI_NG']->fields()->updateOrCreate(
                ['field_key' => $f['key']],
                [
                    'field_name' => $f['name'],
                    'field_type' => 'number',
                    'is_required' => false,
                    'is_editable' => true,
                    'calculation_formula' => null,
                    'unit' => 'pcs',
                    'sort_order' => ($idx + 1) * 10,
                ]
            );
        }

        $templates['TPL_QC_CLAIM_CUST'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_QC_CLAIM_CUST'],
            [
                'name' => 'QC Special Template (Claim Customer)',
                'description' => 'Actual (ppm) computed by formula: (Actual NG / Actual Delv) * 1,000,000.',
                'target_unit' => 'ppm',
                'actual_mode' => 'aggregated',
                'actual_aggregation' => 'formula',
                'actual_field_keys' => null,
                'actual_formula' => '=(actual_ng/actual_delv)*1000000',
                'is_active' => true,
                'sort_order' => 120,
            ]
        );

        foreach ([
            ['key' => 'actual_delv', 'name' => 'Actual Delv (pcs)', 'unit' => 'pcs'],
            ['key' => 'actual_ng', 'name' => 'Actual NG (pcs)', 'unit' => 'pcs'],
            ['key' => 'total_cust', 'name' => 'Total Cust', 'unit' => null],
        ] as $idx => $f) {
            $templates['TPL_QC_CLAIM_CUST']->fields()->updateOrCreate(
                ['field_key' => $f['key']],
                [
                    'field_name' => $f['name'],
                    'field_type' => 'number',
                    'is_required' => false,
                    'is_editable' => true,
                    'calculation_formula' => null,
                    'unit' => $f['unit'],
                    'sort_order' => ($idx + 1) * 10,
                ]
            );
        }

        // 8) QC CopQ (Money related) (Actual = sum of additional fields)
        $templates['TPL_QC_COPQ'] = KpiTemplate::updateOrCreate(
            ['code' => 'TPL_QC_COPQ'],
            [
                'name' => 'QC Special Template (CopQ)',
                'description' => 'Actual is sum of QA Tugas Luar..Waste PD3 (money related).',
                'target_unit' => 'Rp',
                'actual_mode' => 'aggregated',
                'actual_aggregation' => 'sum',
                'actual_field_keys' => [
                    'qa_tugas_luar',
                    'fi',
                    'pd1',
                    'pd2',
                    'pd3',
                    'pd4',
                    'pd5',
                    'supp',
                    'waste_pd3',
                ],
                'actual_formula' => null,
                'is_active' => true,
                'sort_order' => 130,
            ]
        );

        foreach ([
            ['key' => 'qa_tugas_luar', 'name' => 'QA Tugas Luar'],
            ['key' => 'fi', 'name' => 'FI'],
            ['key' => 'pd1', 'name' => 'PD1'],
            ['key' => 'pd2', 'name' => 'PD2'],
            ['key' => 'pd3', 'name' => 'PD3'],
            ['key' => 'pd4', 'name' => 'PD4'],
            ['key' => 'pd5', 'name' => 'PD5'],
            ['key' => 'supp', 'name' => 'Supp'],
            ['key' => 'waste_pd3', 'name' => 'Waste PD3'],
        ] as $idx => $f) {
            $templates['TPL_QC_COPQ']->fields()->updateOrCreate(
                ['field_key' => $f['key']],
                [
                    'field_name' => $f['name'],
                    'field_type' => 'accounting',
                    'is_required' => false,
                    'is_editable' => true,
                    'calculation_formula' => null,
                    'unit' => 'Rp',
                    'sort_order' => ($idx + 1) * 10,
                ]
            );
        }

        // ---- KPI Definitions (Assignments) + Yearly Targets (2026) ----
        $definitions = [];

        // PPIC (code in seed data is PC)
        $definitions = array_merge($definitions, [
            ['dept' => 'PC', 'template' => 'TPL_NORMAL', 'name' => 'On Time Kedatangan Subcont', 'unit' => '%', 'target' => 100, 'op' => 'gte'],
            ['dept' => 'PC', 'template' => 'TPL_NORMAL', 'name' => 'On Time Preparation', 'unit' => '%', 'target' => 100, 'op' => 'gte'],
            ['dept' => 'PC', 'template' => 'TPL_NORMAL', 'name' => 'Mistake Delivery', 'unit' => 'case', 'target' => 0, 'op' => 'lte'],
            ['dept' => 'PC', 'template' => 'TPL_NORMAL', 'name' => 'Cripple', 'unit' => 'ppm', 'target' => 0, 'op' => 'lte'],
            ['dept' => 'PC', 'template' => 'TPL_NORMAL', 'name' => 'Line Stop', 'unit' => 'min', 'target' => 0, 'op' => 'lte'],
            ['dept' => 'PC', 'template' => 'TPL_NORMAL', 'name' => 'On Time Delivery', 'unit' => '%', 'target' => 100, 'op' => 'gte'],
            ['dept' => 'PC', 'template' => 'TPL_NORMAL', 'name' => 'Mis Part', 'unit' => 'ppm', 'target' => 0, 'op' => 'lte'],

            ['dept' => 'PC', 'template' => 'TPL_SPECIAL_PD1_PD5_AVG', 'name' => 'Plan vs Actual PD', 'unit' => '%', 'target' => 100, 'op' => 'gte', 'field_units' => ['pd1' => '%', 'pd2' => '%', 'pd3' => '%', 'pd4' => '%', 'pd5' => '%']],
            ['dept' => 'PC', 'template' => 'TPL_SPECIAL_PD1_PD5_AVG', 'name' => 'Level Stock', 'unit' => 'Day', 'target' => 3, 'op' => 'gte', 'field_units' => ['pd1' => 'Day', 'pd2' => 'Day', 'pd3' => 'Day', 'pd4' => 'Day', 'pd5' => 'Day']],

            ['dept' => 'PC', 'template' => 'TPL_PPIC_SHORTAGE', 'name' => 'Shortage', 'unit' => 'ppm', 'target' => 0, 'op' => 'lte'],
        ]);

        // PD
        $definitions = array_merge($definitions, [
            ['dept' => 'PD', 'template' => 'TPL_NORMAL', 'name' => 'OEE', 'unit' => '%', 'target' => 85.0, 'op' => 'gte'],
            ['dept' => 'PD', 'template' => 'TPL_PD_REJECTION_PROSES', 'name' => 'Rejection in Proses', 'unit' => 'ppm', 'target' => 68, 'op' => 'lte'],
            ['dept' => 'PD', 'template' => 'TPL_PD_MP_OT', 'name' => 'MP & OT', 'unit' => '%', 'target' => 100, 'op' => 'gte'],
            ['dept' => 'PD', 'template' => 'TPL_NORMAL', 'name' => 'Man Power Productivity (PMH Direct)', 'unit' => 'pmh direct', 'target' => 130, 'op' => 'gte'],
            ['dept' => 'PD', 'template' => 'TPL_NORMAL', 'name' => 'Man Power Productivity (PMH Direct + Indirect)', 'unit' => 'pmh dir+indir', 'target' => 108, 'op' => 'gte'],
        ]);

        // QC
        $definitions = array_merge($definitions, [
            ['dept' => 'QC', 'template' => 'TPL_NORMAL', 'name' => 'Warranty Claim', 'unit' => 'case', 'target' => 0, 'op' => 'lte'],
            ['dept' => 'QC', 'template' => 'TPL_NORMAL', 'name' => 'Plan vs Actual Final Inspect', 'unit' => '%', 'target' => 100, 'op' => 'gte'],

            ['dept' => 'QC', 'template' => 'TPL_QC_REJECTION_INCOMING', 'name' => 'Rejection Incoming BB-SBB', 'unit' => 'ppm', 'target' => 10, 'op' => 'lte'],
            ['dept' => 'QC', 'template' => 'TPL_QC_REJECTION_INCOMING', 'name' => 'Rejection Incoming PL', 'unit' => 'ppm', 'target' => 10, 'op' => 'lte'],
            ['dept' => 'QC', 'template' => 'TPL_QC_REJECTION_FI_NC', 'name' => 'Rejection FI_NC', 'unit' => 'ppm', 'target' => 3000, 'op' => 'lte'],
            ['dept' => 'QC', 'template' => 'TPL_QC_REJECTION_FI_NG', 'name' => 'Rejection FI_NG', 'unit' => 'ppm', 'target' => 3000, 'op' => 'lte'],
            ['dept' => 'QC', 'template' => 'TPL_QC_CLAIM_CUST', 'name' => 'Claim Customer', 'unit' => 'ppm', 'target' => 5, 'op' => 'lte'],
            ['dept' => 'QC', 'template' => 'TPL_QC_COPQ', 'name' => 'CopQ', 'unit' => 'Rp', 'target' => 0, 'op' => 'gte'],
        ]);

        // MN
        $definitions = array_merge($definitions, [
            ['dept' => 'MN', 'template' => 'TPL_NORMAL', 'name' => 'Downtime Machine MN', 'unit' => '%', 'target' => 0.88, 'op' => 'lte'],
            ['dept' => 'MN', 'template' => 'TPL_NORMAL', 'name' => 'MTTR MN', 'unit' => 'Hour', 'target' => 2, 'op' => 'lte'],
            ['dept' => 'MN', 'template' => 'TPL_NORMAL', 'name' => 'MTBF MN', 'unit' => 'Hour', 'target' => 400, 'op' => 'gte'],
        ]);

        // HR
        $definitions = array_merge($definitions, [
            ['dept' => 'HR', 'template' => 'TPL_NORMAL', 'name' => 'Absenteism', 'unit' => '%', 'target' => 0.6, 'op' => 'lte'],
            ['dept' => 'HR', 'template' => 'TPL_NORMAL', 'name' => 'Tunjuk Kanan Kiri Sebelum Menyebrang', 'unit' => '%', 'target' => 100, 'op' => 'gte'],
            ['dept' => 'HR', 'template' => 'TPL_NORMAL', 'name' => 'Naik Turun Tangga Memegang Handrail', 'unit' => '%', 'target' => 100, 'op' => 'gte'],
            ['dept' => 'HR', 'template' => 'TPL_NORMAL', 'name' => 'Tidak Menggunakan Ponsel Saat Berjalan', 'unit' => '%', 'target' => 100, 'op' => 'gte'],
            ['dept' => 'HR', 'template' => 'TPL_NORMAL', 'name' => 'Tidak Memasukkan Tangan ke Dalam Saku', 'unit' => '%', 'target' => 100, 'op' => 'gte'],
            ['dept' => 'HR', 'template' => 'TPL_NORMAL', 'name' => 'Berjalan di Area Pejalan Kaki', 'unit' => '%', 'target' => 100, 'op' => 'gte'],

            ['dept' => 'HR', 'template' => 'TPL_HR_WASTE_GRAM', 'name' => 'Waste Gram', 'unit' => 'kg', 'target' => 970.7, 'op' => 'lte'],
            ['dept' => 'HR', 'template' => 'TPL_HR_WASTE_NG', 'name' => 'Waste NG', 'unit' => 'kg', 'target' => 42.5, 'op' => 'lte'],
            ['dept' => 'HR', 'template' => 'TPL_HR_WASTE_PUNTUNGAN', 'name' => 'Waste Puntungan', 'unit' => 'kg', 'target' => 42.5, 'op' => 'lte'],

            ['dept' => 'HR', 'template' => 'TPL_HR_ACCIDENT_MAIN', 'name' => 'Main KPI Accident', 'unit' => 'case', 'target' => 0, 'op' => 'lte'],
        ]);

        // MK
        $definitions = array_merge($definitions, [
            ['dept' => 'MK', 'template' => 'TPL_NORMAL', 'name' => 'On Time Receiving Order', 'unit' => '%', 'target' => 100, 'op' => 'gte'],
        ]);

        // PS
        $definitions = array_merge($definitions, [
            ['dept' => 'PS', 'template' => 'TPL_NORMAL', 'name' => 'On Time Delivery Supplier', 'unit' => '%', 'target' => 89, 'op' => 'gte'],

            // Waste CNC Bending: "No target" in practice (seed target=0 with operator gte so it never goes NG)
            ['dept' => 'PD', 'template' => 'TPL_PD_WASTE_CNC_BENDING', 'name' => 'Waste CNC Bending', 'unit' => '%', 'target' => 0, 'op' => 'gte'],
        ]);

        $sortCounters = [];
        $createdDefinitions = 0;
        $createdTargets = 0;

        foreach ($definitions as $row) {
            $dept = $requireDept($row['dept']);
            $tplCode = $row['template'];
            $tpl = $templates[$tplCode] ?? null;
            if (!$tpl) {
                throw new \RuntimeException("Template '{$tplCode}' not found in seeder.");
            }

            $sortCounters[$dept->code] = ($sortCounters[$dept->code] ?? 0) + 1;
            $sortOrder = $sortCounters[$dept->code] * 10;

            $definition = KpiDefinition::updateOrCreate(
                [
                    'department_id' => (int) $dept->id,
                    'kpi_template_id' => (int) $tpl->id,
                    'display_name' => (string) $row['name'],
                ],
                [
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                    'field_units' => $row['field_units'] ?? null,
                ]
            );
            $createdDefinitions++;

            $target = KpiMonthlyTarget::updateOrCreate(
                [
                    'kpi_definition_id' => (int) $definition->id,
                    'target_year' => (int) $year,
                    'target_month' => (int) $month,
                ],
                [
                    'kpi_template_id' => (int) $tpl->id,
                    'department_id' => (int) $dept->id,
                    'target_value' => (float) $row['target'],
                    'target_operator' => (string) ($row['op'] ?? 'gte'),
                    'target_unit' => (string) ($row['unit'] ?? ($tpl->target_unit ?? '%')),
                    'notes' => null,
                    'created_by' => null,
                    'updated_at' => Carbon::now(),
                ]
            );

            if ($target->wasRecentlyCreated) {
                $createdTargets++;
            }
        }

        $this->command->info('✓ KPI templates & fields seeded: ' . count($templates));
        $this->command->info('✓ KPI definitions (assignments) upserted: ' . $createdDefinitions);
        $this->command->info('✓ KPI yearly targets created: ' . $createdTargets . ' (year ' . $year . ')');
    }
}
