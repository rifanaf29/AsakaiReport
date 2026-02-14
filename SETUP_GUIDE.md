# Quick Setup Guide - KPI & CAPA Reporting System

## Installation Steps

### 1. Run Migrations

```bash
# Run all migrations to create database tables
php artisan migrate

# If you need to start fresh (WARNING: drops all tables)
php artisan migrate:fresh
```

### 2. Setup Role-Based Access Control (RBAC)

**Important:** Before creating users, set up the role and permission system.

```bash
# Install Spatie Permission package
composer require spatie/laravel-permission

# Publish Spatie configuration and migrations
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"

# Run migrations (includes Spatie tables and department field for users)
php artisan migrate

# Seed roles and permissions
php artisan db:seed --class=RolesAndPermissionsSeeder
```

This creates:
- ✅ **3 Roles:** admin, manager, user
- ✅ **30+ Permissions** for KPI, CAPA, Departments, Users, Reports
- ✅ **Department access control** (users can only access their own department)

**📖 Detailed RBAC Setup:** See [RBAC_SETUP_GUIDE.md](RBAC_SETUP_GUIDE.md)  
**📖 Complete RBAC Documentation:** See [RBAC_DOCUMENTATION.md](RBAC_DOCUMENTATION.md)

### 3. Create Sample Departments (Optional)

```bash
# Create a seeder
php artisan make:seeder DepartmentSeeder
```

Edit the seeder file:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Department;

class DepartmentSeeder extends Seeder
{
    public function run()
    {
        $departments = [
            ['code' => 'PROD', 'name' => 'Production', 'description' => 'Production Department', 'is_active' => true],
            ['code' => 'QA', 'name' => 'Quality Assurance', 'description' => 'Quality Assurance Department', 'is_active' => true],
            ['code' => 'LOG', 'name' => 'Logistics', 'description' => 'Logistics Department', 'is_active' => true],
            ['code' => 'HR', 'name' => 'Human Resources', 'description' => 'Human Resources Department', 'is_active' => true],
            ['code' => 'FIN', 'name' => 'Finance', 'description' => 'Finance Department', 'is_active' => true],
        ];

        foreach ($departments as $dept) {
            Department::create($dept);
        }
    }
}
```

Run the seeder:
```bash
php artisan db:seed --class=DepartmentSeeder
```

### 3. File Structure Overview

```
app/
├── Models/
│   ├── Department.php           ✓ Created
│   ├── KpiTemplate.php          ✓ Created
│   ├── KpiTemplateField.php     ✓ Created
│   ├── KpiEntry.php             ✓ Created
│   ├── CapaArea.php             ✓ Created
│   ├── CapaProblem.php          ✓ Created
│   ├── CapaCause.php            ✓ Created
│   └── CapaActionPlan.php       ✓ Created
│
├── Observers/
│   └── KpiEntryObserver.php     ✓ Created
│
├── Http/
│   └── Requests/
│       └── StoreKpiEntryRequest.php  ✓ Created
│
└── Providers/
    └── AppServiceProvider.php   ✓ Updated (Observer registered)

database/
└── migrations/
    ├── 2024_01_01_000001_create_departments_table.php         ✓ Created
    ├── 2024_01_01_000002_create_kpi_templates_table.php       ✓ Created
    ├── 2024_01_01_000003_create_kpi_template_fields_table.php ✓ Created
    ├── 2024_01_01_000004_create_kpi_entries_table.php         ✓ Created
    ├── 2024_01_01_000005_create_capa_areas_table.php          ✓ Created
    ├── 2024_01_01_000006_create_capa_problems_table.php       ✓ Created
    ├── 2024_01_01_000007_create_capa_causes_table.php         ✓ Created
    └── 2024_01_01_000008_create_capa_action_plans_table.php   ✓ Created
```

---

## Testing the Setup

### Create Test Data via Tinker

```bash
php artisan tinker
```

```php
// 1. Create a department
$dept = \App\Models\Department::create([
    'code' => 'TEST',
    'name' => 'Test Department',
    'description' => 'For testing',
    'is_active' => true
]);

// 2. Create a KPI template
$template = $dept->kpiTemplates()->create([
    'name' => 'Quality Sheet',
    'code' => 'QTY_001',
    'description' => 'Daily quality metrics',
    'target_unit' => '%',
    'is_active' => true
]);

// 3. Create template fields
$template->fields()->create([
    'field_name' => 'PD1',
    'field_key' => 'pd1',
    'field_type' => 'decimal',
    'is_required' => true,
    'sort_order' => 1
]);

// 4. Create a KPI entry
$entry = \App\Models\KpiEntry::create([
    'kpi_template_id' => $template->id,
    'department_id' => $dept->id,
    'entry_date' => '2024-02-14',
    'target' => 95.00,
    'actual' => 92.00,
    'status' => 'NG',
    'dynamic_fields' => ['pd1' => 92.0],
    'created_by' => 1,
]);

// 5. Check if CAPA is required
$entry->requiresCapa(); // Should return true

// 6. Create CAPA
$capa = \App\Models\CapaArea::create([
    'department_id' => $dept->id,
    'kpi_entry_id' => $entry->id,
    'capa_date' => '2024-02-14',
    'area_name' => 'Production Line 1',
    'is_mandatory' => true,
    'created_by' => 1,
]);

// 7. Create problem
$problem = $capa->problems()->create([
    'problem_description' => 'High defect rate',
    'problem_category' => 'Quality',
    'severity' => 'high',
    'created_by' => 1,
]);

// 8. Create cause
$cause = $problem->causes()->create([
    'cause_description' => 'Machine calibration issue',
    'cause_type' => 'Machine',
    'created_by' => 1,
]);

// 9. Create action plan
$action = $cause->actionPlans()->create([
    'description' => 'Recalibrate machine',
    'pic_user_id' => 1,
    'due_date' => '2024-02-20',
    'keterangan' => 'High priority',
    'status' => 'open',
    'created_by' => 1,
]);

// Verify relationships
$entry->capaAreas; // Should show 1 area
$capa->problems; // Should show 1 problem
$problem->causes; // Should show 1 cause
$cause->actionPlans; // Should show 1 action plan
$action->isOverdue(); // Check if overdue
```

---

## Next Steps - Create Controllers

### 1. Generate Controllers

```bash
php artisan make:controller Api/DepartmentController --api
php artisan make:controller Api/KpiTemplateController --api
php artisan make:controller Api/KpiEntryController --api
php artisan make:controller Api/CapaAreaController --api
php artisan make:controller Api/CapaActionPlanController --api
```

### 2. Example Controller Structure

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KpiEntry;
use App\Http\Requests\StoreKpiEntryRequest;
use Illuminate\Http\Request;

class KpiEntryController extends Controller
{
    public function index(Request $request)
    {
        $query = KpiEntry::with(['department', 'template', 'creator']);

        // Filter by department
        if ($request->has('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        // Filter by date range
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->dateRange($request->start_date, $request->end_date);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        return $query->latest('entry_date')->paginate(50);
    }

    public function store(StoreKpiEntryRequest $request)
    {
        $kpiEntry = KpiEntry::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        $warning = null;
        if ($kpiEntry->requiresCapa() && !$kpiEntry->hasCapaFilled()) {
            $warning = 'KPI status is NG. Please fill CAPA immediately.';
        }

        return response()->json([
            'message' => 'KPI entry created successfully',
            'data' => $kpiEntry->load('department', 'template'),
            'warning' => $warning,
            'capa_required' => $kpiEntry->requiresCapa(),
        ], 201);
    }

    public function show(KpiEntry $kpiEntry)
    {
        return $kpiEntry->load(['department', 'template', 'capaAreas.problems.causes.actionPlans']);
    }

    public function update(StoreKpiEntryRequest $request, KpiEntry $kpiEntry)
    {
        // Check if entry is locked
        if ($kpiEntry->is_locked) {
            return response()->json(['message' => 'This entry is locked and cannot be modified.'], 403);
        }

        $kpiEntry->update([
            ...$request->validated(),
            'updated_by' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'KPI entry updated successfully',
            'data' => $kpiEntry->fresh()->load('department', 'template'),
        ]);
    }

    public function destroy(KpiEntry $kpiEntry)
    {
        if ($kpiEntry->capaAreas()->exists()) {
            return response()->json([
                'message' => 'Cannot delete KPI entry with linked CAPA areas.',
            ], 400);
        }

        $kpiEntry->delete();

        return response()->json(['message' => 'KPI entry deleted successfully']);
    }

    /**
     * Get KPI summary/dashboard
     */
    public function dashboard(Request $request)
    {
        $departmentId = $request->input('department_id');
        $startDate = $request->input('start_date', now()->startOfMonth());
        $endDate = $request->input('end_date', now()->endOfMonth());

        $summary = KpiEntry::where('department_id', $departmentId)
            ->dateRange($startDate, $endDate)
            ->selectRaw('
                COUNT(*) as total_entries,
                SUM(CASE WHEN status = "OK" THEN 1 ELSE 0 END) as ok_count,
                SUM(CASE WHEN status = "NG" THEN 1 ELSE 0 END) as ng_count,
                SUM(CASE WHEN status = "PENDING" THEN 1 ELSE 0 END) as pending_count,
                ROUND(AVG(actual), 2) as avg_actual,
                ROUND(AVG(target), 2) as avg_target
            ')
            ->first();

        return response()->json($summary);
    }
}
```

### 3. Register Routes

Edit `routes/api.php`:

```php
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\KpiEntryController;
use App\Http\Controllers\Api\CapaAreaController;

Route::middleware('auth:sanctum')->group(function () {
    // Departments
    Route::apiResource('departments', DepartmentController::class);
    
    // KPI
    Route::get('kpi/dashboard', [KpiEntryController::class, 'dashboard']);
    Route::apiResource('kpi-entries', KpiEntryController::class);
    
    // CAPA
    Route::apiResource('capa-areas', CapaAreaController::class);
});
```

---

## Database Indexes Performance Check

After migrations, verify indexes:

```sql
-- MySQL
SHOW INDEXES FROM kpi_entries;
SHOW INDEXES FROM capa_action_plans;

-- Check query performance
EXPLAIN SELECT * FROM kpi_entries 
WHERE department_id = 1 
AND entry_date BETWEEN '2024-01-01' AND '2024-01-31'
AND status = 'NG';
```

---

## Common Queries Reference

```php
// Get all KPI entries for a department this month
KpiEntry::where('department_id', $deptId)
    ->whereMonth('entry_date', now()->month)
    ->with('template')
    ->latest('entry_date')
    ->get();

// Get all NG status entries without CAPA
KpiEntry::ngStatus()
    ->doesntHave('capaAreas')
    ->with('department')
    ->get();

// Get overdue action plans for a user
CapaActionPlan::byPic($userId)
    ->overdue()
    ->with('cause.problem.area')
    ->get();

// Get action plans due within 3 days
CapaActionPlan::dueWithinDays(3)
    ->with('pic', 'cause.problem.area.department')
    ->orderBy('due_date')
    ->get();

// Get CAPA completion rate for department
$capaAreas = CapaArea::where('department_id', $deptId)
    ->with('problems.causes.actionPlans')
    ->get();

$completionRates = $capaAreas->map(function ($area) {
    return [
        'area' => $area->area_name,
        'date' => $area->capa_date,
        'completion' => $area->getActionPlanCompletionPercentage(),
    ];
});
```

---

## Troubleshooting

### Issue: Migration fails with foreign key error
**Solution:** Run migrations in order. The file names are numbered to ensure proper order.

### Issue: Observer not triggering
**Solution:** Make sure `KpiEntry::observe(KpiEntryObserver::class);` is in `AppServiceProvider::boot()`.

### Issue: JSON field not working
**Solution:** Ensure your database supports JSON (MySQL 5.7+, PostgreSQL 9.4+). Check that `dynamic_fields` is cast to `'array'` in the model.

### Issue: Slow queries on large datasets
**Solution:** 
1. Add more specific indexes
2. Use eager loading (`with()`)
3. Implement caching for dashboard queries
4. Consider read replicas for reporting

---

## Production Checklist

- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Set up database backups
- [ ] Configure queue workers for notifications
- [ ] Implement proper authentication (Sanctum tokens)
- [ ] Add rate limiting to API routes
- [ ] Set up monitoring (Laravel Telescope, Horizon)
- [ ] Configure Redis for caching
- [ ] Implement proper error handling
- [ ] Write automated tests
- [ ] Set up CI/CD pipeline

---

## Additional Resources

- **Main Documentation:** [DATABASE_ARCHITECTURE.md](DATABASE_ARCHITECTURE.md)
- **Laravel Eloquent:** https://laravel.com/docs/eloquent
- **Laravel Validation:** https://laravel.com/docs/validation
- **Laravel Observers:** https://laravel.com/docs/eloquent#observers
- **Query Optimization:** https://laravel.com/docs/queries#debugging

---

## Support

For questions or issues with this implementation:
1. Review the main documentation in `DATABASE_ARCHITECTURE.md`
2. Check Laravel logs: `storage/logs/laravel.log`
3. Use `php artisan tinker` to test relationships
4. Run `php artisan route:list` to see all routes
5. Check database with `php artisan db:show`
