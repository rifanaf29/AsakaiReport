# Quick Reference - Developer Cheat Sheet

## 🚀 Installation Commands

```bash
# Initial setup
composer require spatie/laravel-permission
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan migrate
php artisan db:seed --class=RolesAndPermissionsSeeder

# Clear permission cache
php artisan permission:cache-reset
```

---

## 👥 User Management

### Create User with Role
```php
$user = User::create([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => bcrypt('password'),
    'department_id' => 1,
    'can_access_all_departments' => false,
]);
$user->assignRole('manager');
```

### Check User Permissions
```php
$user->hasRole('admin');                    // Check role
$user->can('edit kpi');                     // Check permission
$user->hasAnyRole(['admin', 'manager']);    // Check multiple roles
$user->canAccessDepartment(1);              // Check department access
$user->isAdmin();                           // Helper method
```

### Manage Roles
```php
$user->assignRole('manager');               // Assign role
$user->removeRole('user');                  // Remove role
$user->syncRoles(['manager']);              // Replace all roles
$user->getRoleNames();                      // Get all roles
```

---

## 🔐 Authorization

### In Controllers
```php
// Method 1: authorize() helper
$this->authorize('view', $kpiEntry);
$this->authorize('update', $kpiEntry);

// Method 2: Gate facade
if (Gate::allows('update', $kpiEntry)) {
    // Authorized
}

// Method 3: User can()
if (auth()->user()->can('update', $kpiEntry)) {
    // Authorized
}
```

### In Routes
```php
// Role-based protection
Route::middleware('role:admin')->group(function () {
    Route::apiResource('departments', DepartmentController::class);
});

// Permission-based protection
Route::middleware('permission:edit kpi')->group(function () {
    Route::put('/kpi/{id}', [KpiController::class, 'update']);
});

// Multiple roles
Route::middleware('role:admin|manager')->group(function () {
    Route::delete('/kpi/{id}', [KpiController::class, 'destroy']);
});

// Combined with department access check
Route::middleware(['auth:sanctum', 'department.access', 'role:manager'])->group(function () {
    // Protected routes
});
```

### In Blade Views
```blade
@role('admin')
    <button>Admin Only</button>
@endrole

@can('edit kpi')
    <button>Edit KPI</button>
@endcan

@cannot('delete kpi')
    <span>Delete not allowed</span>
@endcannot

@if(auth()->user()->canAccessDepartment($department->id))
    <a href="#">View Department</a>
@endif
```

---

## 📊 KPI Module

### Create KPI Template
```php
$template = KpiTemplate::create([
    'department_id' => 1,
    'name' => 'Quality Sheet',
    'code' => 'QS001',
    'target_unit' => '%',
    'is_active' => true,
]);

// Add fields
$template->fields()->create([
    'field_name' => 'PD1',
    'field_key' => 'pd1',
    'field_type' => 'decimal',
    'is_required' => true,
]);
```

### Create KPI Entry
```php
$entry = KpiEntry::create([
    'kpi_template_id' => 1,
    'department_id' => 1,
    'entry_date' => today(),
    'target' => 95.00,
    'actual' => 92.00,
    'status' => 'NG',
    'dynamic_fields' => ['pd1' => 92.0],
    'created_by' => auth()->id(),
]);

// Check if CAPA required
if ($entry->requiresCapa()) {
    // Show CAPA form
}
```

### Query KPI Entries
```php
// By department (filtered)
$entries = KpiEntry::when(!auth()->user()->isAdmin(), function ($query) {
    $query->where('department_id', auth()->user()->department_id);
})->with('department', 'template')->get();

// Date range
$entries = KpiEntry::dateRange('2024-01-01', '2024-01-31')->get();

// NG status only
$ngEntries = KpiEntry::ngStatus()->get();

// With department and date
$entries = KpiEntry::byDepartmentAndDateRange(1, '2024-01-01', '2024-01-31')->get();
```

---

## 🔧 CAPA Module

### Create Complete CAPA
```php
// Create Area
$area = CapaArea::create([
    'department_id' => 1,
    'kpi_entry_id' => 1,
    'capa_date' => today(),
    'area_name' => 'Production Line 1',
    'is_mandatory' => true,
]);

// Create Problem
$problem = $area->problems()->create([
    'problem_description' => 'High defect rate',
    'severity' => 'high',
]);

// Create Cause
$cause = $problem->causes()->create([
    'cause_description' => 'Machine calibration issue',
    'cause_type' => 'Machine',
]);

// Create Action Plan
$action = $cause->actionPlans()->create([
    'description' => 'Recalibrate machine',
    'pic_user_id' => 5,
    'due_date' => now()->addDays(7),
    'status' => 'open',
]);
```

### Query CAPA Data
```php
// All CAPA areas with full hierarchy
$capaAreas = CapaArea::with([
    'problems.causes.actionPlans.pic'
])->get();

// By department
$capaAreas = CapaArea::where('department_id', 1)->get();

// Mandatory CAPA only
$mandatoryCapa = CapaArea::mandatory()->get();

// Date range
$capaAreas = CapaArea::dateRange('2024-01-01', '2024-01-31')->get();

// Overdue action plans
$overdue = CapaActionPlan::overdue()->with('pic')->get();

// Action plans due within 3 days
$upcoming = CapaActionPlan::dueWithinDays(3)->get();

// By PIC
$myActions = CapaActionPlan::byPic(auth()->id())->get();
```

### Check Action Plan Status
```php
$actionPlan->isOverdue();           // bool
$actionPlan->daysUntilDue();        // int (negative if overdue)
$actionPlan->overdue_status;        // ['status' => 'overdue', 'color' => 'danger']

// Get completion percentage for area
$area->getActionPlanCompletionPercentage(); // 0-100
$area->allActionPlansClosed();              // bool
```

---

## 📈 Dashboard Queries

### KPI Summary
```php
$summary = KpiEntry::where('department_id', $deptId)
    ->whereMonth('entry_date', now()->month)
    ->selectRaw('
        COUNT(*) as total,
        SUM(CASE WHEN status = "OK" THEN 1 ELSE 0 END) as ok_count,
        SUM(CASE WHEN status = "NG" THEN 1 ELSE 0 END) as ng_count,
        ROUND(AVG(actual), 2) as avg_actual
    ')
    ->first();
```

### Department Performance
```php
$performance = Department::withCount([
    'kpiEntries',
    'kpiEntries as ng_count' => function ($query) {
        $query->where('status', 'NG');
    }
])->get();
```

### Overdue Actions by Department
```php
$overdueByDept = CapaActionPlan::overdue()
    ->join('capa_causes', 'capa_action_plans.capa_cause_id', '=', 'capa_causes.id')
    ->join('capa_problems', 'capa_causes.capa_problem_id', '=', 'capa_problems.id')
    ->join('capa_areas', 'capa_problems.capa_area_id', '=', 'capa_areas.id')
    ->selectRaw('capa_areas.department_id, COUNT(*) as overdue_count')
    ->groupBy('capa_areas.department_id')
    ->get();
```

---

## 🎯 Model Scopes

### User Scopes
```php
User::byDepartment($deptId);
User::accessible(auth()->user());
```

### KpiEntry Scopes
```php
KpiEntry::ngStatus();
KpiEntry::dateRange($start, $end);
KpiEntry::byDepartmentAndDateRange($deptId, $start, $end);
```

### CapaArea Scopes
```php
CapaArea::mandatory();
CapaArea::dateRange($start, $end);
```

### CapaActionPlan Scopes
```php
CapaActionPlan::open();
CapaActionPlan::inProgress();
CapaActionPlan::closed();
CapaActionPlan::overdue();
CapaActionPlan::dueWithinDays($days);
CapaActionPlan::byPic($userId);
```

---

## 🔍 Relationships

### User
```php
$user->department;          // BelongsTo Department
```

### Department
```php
$department->kpiTemplates;  // HasMany KpiTemplate
$department->kpiEntries;    // HasMany KpiEntry
$department->capaAreas;     // HasMany CapaArea
```

### KpiEntry
```php
$entry->template;           // BelongsTo KpiTemplate
$entry->department;         // BelongsTo Department
$entry->creator;            // BelongsTo User
$entry->capaAreas;          // HasMany CapaArea
```

### CapaArea
```php
$area->department;          // BelongsTo Department
$area->kpiEntry;            // BelongsTo KpiEntry (nullable)
$area->problems;            // HasMany CapaProblem
$area->causes;              // HasManyThrough CapaCause
```

### CapaActionPlan
```php
$action->cause;             // BelongsTo CapaCause
$action->pic;               // BelongsTo User
$action->creator;           // BelongsTo User
```

---

## 🛠️ Useful Artisan Commands

```bash
# Migrations
php artisan migrate                     # Run migrations
php artisan migrate:rollback            # Rollback last batch
php artisan migrate:fresh               # Drop all tables and re-run
php artisan migrate:status              # Check migration status

# Seeders
php artisan db:seed                     # Run all seeders
php artisan db:seed --class=RolesAndPermissionsSeeder

# Cache
php artisan config:cache                # Cache config
php artisan route:cache                 # Cache routes
php artisan view:cache                  # Cache views
php artisan permission:cache-reset      # Clear permission cache

# Database
php artisan db:show                     # Show database info
php artisan tinker                      # Open REPL

# Development
php artisan serve                       # Start dev server
php artisan queue:work                  # Run queue worker
```

---

## 🧪 Testing Checklist

### Test User Permissions
```php
// In tinker
$user = User::find(1);
$user->hasRole('admin');
$user->can('edit kpi');
$user->canAccessDepartment(1);
$user->getAllPermissions();
```

### Test KPI Business Rules
```php
$entry = KpiEntry::find(1);
$entry->requiresCapa();         // True if status is NG
$entry->hasCapaFilled();        // True if CAPA exists
```

### Test Department Access
```php
$user = User::find(1);
$entry = KpiEntry::find(1);

// Should pass for user's department
$user->canAccessDepartment($entry->department_id);

// Should fail for other departments (unless admin)
$user->canAccessDepartment(999);
```

---

## 📦 Common Patterns

### Controller Authorization Pattern
```php
public function update(Request $request, KpiEntry $kpiEntry)
{
    // 1. Authorize
    $this->authorize('update', $kpiEntry);
    
    // 2. Validate
    $validated = $request->validate([
        'actual' => 'required|numeric',
        'status' => 'required|in:OK,NG,PENDING',
    ]);
    
    // 3. Update
    $kpiEntry->update([
        ...$validated,
        'updated_by' => auth()->id(),
    ]);
    
    // 4. Return
    return response()->json($kpiEntry);
}
```

### Department Filtering Pattern
```php
public function index(Request $request)
{
    $query = KpiEntry::with(['department', 'template']);
    
    // Auto-filter by department (except admin)
    if (!auth()->user()->isAdmin()) {
        $query->where('department_id', auth()->user()->department_id);
    }
    
    return $query->paginate(50);
}
```

### Eager Loading Pattern
```php
// Load full CAPA hierarchy efficiently
$capaAreas = CapaArea::with([
    'department:id,name',
    'kpiEntry:id,entry_date,status',
    'problems' => function ($query) {
        $query->with([
            'causes' => function ($query) {
                $query->with([
                    'actionPlans' => function ($query) {
                        $query->with('pic:id,name')
                            ->orderBy('due_date');
                    }
                ]);
            }
        ]);
    }
])->get();
```

---

## 🎨 Naming Conventions

- **Models:** Singular PascalCase (`KpiEntry`, `CapaArea`)
- **Tables:** Plural snake_case (`kpi_entries`, `capa_areas`)
- **Controllers:** Singular + Controller (`KpiEntryController`)
- **Policies:** Singular + Policy (`KpiEntryPolicy`)
- **Middleware:** Descriptive names (`CheckDepartmentAccess`)
- **Routes:** Kebab-case (`/kpi-entries`, `/capa-areas`)
- **Permissions:** Lowercase with spaces (`edit kpi`, `view capa`)
- **Roles:** Lowercase (`admin`, `manager`, `user`)

---

## 📋 Environment Variables

```env
# Required for RBAC
PERMISSION_CACHE_EXPIRATION_TIME=86400

# Recommended for production
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Set in production
APP_ENV=production
APP_DEBUG=false
```

---

## 🆘 Troubleshooting

### Permission not working
```bash
php artisan permission:cache-reset
php artisan config:clear
```

### Migration issues
```bash
php artisan migrate:status
php artisan migrate:rollback
php artisan migrate
```

### Department access denied
```php
// Check user department
$user->department_id; // Should not be null

// Check can_access_all_departments
$user->can_access_all_departments; // Should be true for admin

// Check role
$user->hasRole('admin');
```

---

**For complete documentation, see:**
- [DATABASE_ARCHITECTURE.md](DATABASE_ARCHITECTURE.md)
- [RBAC_DOCUMENTATION.md](RBAC_DOCUMENTATION.md)
- [SETUP_GUIDE.md](SETUP_GUIDE.md)
