# Role-Based Access Control & Department Security

## Overview
This system implements a comprehensive role-based access control (RBAC) using **Spatie Laravel Permission** with additional **department-level access restrictions**.

---

## Table of Contents
1. [Installation Steps](#installation-steps)
2. [Roles & Permissions](#roles--permissions)
3. [Department Access Control](#department-access-control)
4. [Usage Examples](#usage-examples)
5. [Middleware Configuration](#middleware-configuration)
6. [Policy Authorization](#policy-authorization)
7. [Testing](#testing)

---

## Installation Steps

### 1. Install Spatie Permission Package

```bash
composer require spatie/laravel-permission
```

### 2. Publish Configuration and Migrations

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### 3. Run Migrations

```bash
# Run Spatie permission tables
php artisan migrate

# Run custom department field migration
php artisan migrate --path=/database/migrations/2024_01_02_000001_add_department_to_users_table.php
```

### 4. Seed Roles and Permissions

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

This creates:
- **3 Roles:** admin, manager, user
- **30+ Permissions** across KPI, CAPA, Departments, Users, and Reports

---

## Roles & Permissions

### Role Hierarchy

```
┌─────────────┐
│   ADMIN     │  ← Full system access, all departments
└──────┬──────┘
       │
       ▼
┌─────────────┐
│   MANAGER   │  ← Department management, own department only
└──────┬──────┘
       │
       ▼
┌─────────────┐
│    USER     │  ← Basic access, own department only
└─────────────┘
```

---

### 1. ADMIN Role

**Access Level:** Full system access, all departments

**Permissions:**
- ✅ All permissions (30+ permissions)
- ✅ Can access any department
- ✅ Can create/edit/delete departments
- ✅ Can assign roles to users
- ✅ Can lock/unlock KPI entries
- ✅ Can manage system settings
- ✅ Can view audit logs

**Use Cases:**
- System administrators
- Top management
- IT support staff

---

### 2. MANAGER Role

**Access Level:** Department-level management (own department only)

**Permissions:**
- ✅ View departments (own only)
- ✅ Full KPI access (create, edit, delete, lock) within department
- ✅ Full CAPA access (create, edit, delete, assign, close) within department
- ✅ User management (view, create, edit) within department
- ✅ Reports (view, export) for own department

**Use Cases:**
- Department heads
- Team leaders
- Section managers

---

### 3. USER Role

**Access Level:** Basic access (own department only)

**Permissions:**
- ✅ View departments (own only)
- ✅ KPI: view, create, edit (cannot delete or lock)
- ✅ CAPA: view, create, edit (cannot delete or close)
- ✅ Reports: view only

**Use Cases:**
- Regular employees
- Data entry staff
- Team members

---

## Complete Permission List

| Permission | Admin | Manager | User | Description |
|------------|-------|---------|------|-------------|
| **Departments** |
| view departments | ✅ | ✅ | ✅ | View department info |
| create departments | ✅ | ❌ | ❌ | Create new departments |
| edit departments | ✅ | ❌ | ❌ | Edit department info |
| delete departments | ✅ | ❌ | ❌ | Delete departments |
| **KPI** |
| view kpi | ✅ | ✅ | ✅ | View KPI entries |
| create kpi | ✅ | ✅ | ✅ | Create KPI entries |
| edit kpi | ✅ | ✅ | ✅ | Edit KPI entries |
| delete kpi | ✅ | ✅ | ❌ | Delete KPI entries |
| lock kpi | ✅ | ✅ | ❌ | Lock KPI entries |
| unlock kpi | ✅ | ❌ | ❌ | Unlock KPI entries |
| view all department kpi | ✅ | ❌ | ❌ | Cross-department access |
| **CAPA** |
| view capa | ✅ | ✅ | ✅ | View CAPA areas |
| create capa | ✅ | ✅ | ✅ | Create CAPA areas |
| edit capa | ✅ | ✅ | ✅ | Edit CAPA data |
| delete capa | ✅ | ✅ | ❌ | Delete CAPA areas |
| assign capa | ✅ | ✅ | ❌ | Assign action plans |
| close capa | ✅ | ✅ | ❌ | Close action plans |
| view all department capa | ✅ | ❌ | ❌ | Cross-department access |
| **Users** |
| view users | ✅ | ✅ | ❌ | View user list |
| create users | ✅ | ✅ | ❌ | Create new users |
| edit users | ✅ | ✅ | ❌ | Edit user info |
| delete users | ✅ | ❌ | ❌ | Delete users |
| assign roles | ✅ | ❌ | ❌ | Assign user roles |
| **Reports** |
| view reports | ✅ | ✅ | ✅ | View reports |
| export reports | ✅ | ✅ | ❌ | Export to Excel/PDF |
| view all department reports | ✅ | ❌ | ❌ | Cross-department reports |
| **System** |
| manage settings | ✅ | ❌ | ❌ | System settings |
| view audit logs | ✅ | ❌ | ❌ | View audit trail |

---

## Department Access Control

### Database Fields

**users table:**
```php
$table->foreignId('department_id')->nullable(); // User's department
$table->boolean('can_access_all_departments')->default(false); // Override flag
```

### Access Rules

1. **Admin Role:** Always has access to all departments
2. **can_access_all_departments = true:** Bypass department restrictions
3. **Regular Users:** Only access their assigned department

### User Model Methods

```php
// Check if user can access a specific department
$user->canAccessDepartment($departmentId); // bool

// Check user roles
$user->isAdmin(); // bool
$user->isManager(); // bool
$user->isUser(); // bool

// Get user's department
$user->department; // Department model
```

### Query Scopes

```php
// Filter users by department
User::byDepartment($departmentId)->get();

// Get users accessible by current user
User::accessible(auth()->user())->get();
```

---

## Usage Examples

### 1. Create User with Role and Department

```php
use App\Models\User;
use App\Models\Department;

// Create department first
$dept = Department::create([
    'code' => 'PROD',
    'name' => 'Production',
    'is_active' => true,
]);

// Create admin user (access all departments)
$admin = User::create([
    'name' => 'Admin User',
    'email' => 'admin@example.com',
    'password' => bcrypt('password'),
    'department_id' => $dept->id,
    'can_access_all_departments' => true,
]);
$admin->assignRole('admin');

// Create manager user (department-specific)
$manager = User::create([
    'name' => 'Manager User',
    'email' => 'manager@example.com',
    'password' => bcrypt('password'),
    'department_id' => $dept->id,
    'can_access_all_departments' => false,
]);
$manager->assignRole('manager');

// Create regular user (department-specific)
$user = User::create([
    'name' => 'Regular User',
    'email' => 'user@example.com',
    'password' => bcrypt('password'),
    'department_id' => $dept->id,
]);
$user->assignRole('user');
```

---

### 2. Check Permissions in Controllers

```php
use App\Models\KpiEntry;

class KpiEntryController extends Controller
{
    public function index(Request $request)
    {
        // Check permission
        $this->authorize('viewAny', KpiEntry::class);

        $query = KpiEntry::with(['department', 'template']);

        // Filter by department access
        if (!auth()->user()->isAdmin() && !auth()->user()->can_access_all_departments) {
            $query->where('department_id', auth()->user()->department_id);
        }

        return $query->paginate(50);
    }

    public function store(Request $request)
    {
        // Check create permission
        $this->authorize('create', KpiEntry::class);

        // Validate department access
        if (!auth()->user()->canAccessDepartment($request->department_id)) {
            abort(403, 'You cannot create KPI entries for this department.');
        }

        $kpiEntry = KpiEntry::create($request->validated());

        return response()->json($kpiEntry, 201);
    }

    public function update(Request $request, KpiEntry $kpiEntry)
    {
        // Check update permission and department access
        $this->authorize('update', $kpiEntry);

        $kpiEntry->update($request->validated());

        return response()->json($kpiEntry);
    }

    public function destroy(KpiEntry $kpiEntry)
    {
        // Check delete permission and department access
        $this->authorize('delete', $kpiEntry);

        $kpiEntry->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
```

---

### 3. Check Permissions in Blade Views

```blade
@role('admin')
    <button>Delete Department</button>
@endrole

@role('manager|admin')
    <button>Lock KPI Entry</button>
@endrole

@can('edit kpi')
    <button>Edit KPI</button>
@endcan

@cannot('delete capa')
    <span class="text-muted">Delete not allowed</span>
@endcannot

@if(auth()->user()->canAccessDepartment($department->id))
    <a href="{{ route('departments.show', $department) }}">View Details</a>
@endif
```

---

### 4. Route Protection with Middleware

```php
// routes/api.php
use Illuminate\Support\Facades\Route;

// Protected routes with authentication and department access check
Route::middleware(['auth:sanctum', 'department.access'])->group(function () {
    
    // KPI routes - require specific permissions
    Route::middleware('permission:view kpi')->group(function () {
        Route::get('/kpi-entries', [KpiEntryController::class, 'index']);
        Route::get('/kpi-entries/{kpiEntry}', [KpiEntryController::class, 'show']);
    });

    Route::middleware('permission:create kpi')->group(function () {
        Route::post('/kpi-entries', [KpiEntryController::class, 'store']);
    });

    Route::middleware('permission:edit kpi')->group(function () {
        Route::put('/kpi-entries/{kpiEntry}', [KpiEntryController::class, 'update']);
    });

    // Manager and admin only
    Route::middleware('role:manager|admin')->group(function () {
        Route::delete('/kpi-entries/{kpiEntry}', [KpiEntryController::class, 'destroy']);
        Route::post('/kpi-entries/{kpiEntry}/lock', [KpiEntryController::class, 'lock']);
    });

    // Admin only routes
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('departments', DepartmentController::class);
        Route::post('/users/{user}/assign-role', [UserController::class, 'assignRole']);
    });
});
```

---

## Middleware Configuration

### 1. CheckDepartmentAccess Middleware

Automatically validates department access on requests.

**Features:**
- Extracts `department_id` from query, route params, or request body
- Allows admin and users with `can_access_all_departments` flag
- Blocks access with 403 error if unauthorized

**Usage:**
```php
Route::middleware(['auth:sanctum', 'department.access'])->group(function () {
    // All routes here are protected
});
```

---

### 2. Spatie Middleware

**role:** Check user has specific role(s)
```php
Route::middleware('role:admin|manager')->group(function () {
    // Only admin or manager
});
```

**permission:** Check user has specific permission(s)
```php
Route::middleware('permission:edit kpi')->group(function () {
    // Only users with 'edit kpi' permission
});
```

**role_or_permission:** Check user has role OR permission
```php
Route::middleware('role_or_permission:admin|edit kpi')->group(function () {
    // Admin OR anyone with 'edit kpi' permission
});
```

---

## Policy Authorization

Policies provide fine-grained authorization logic for models.

### Created Policies

1. **DepartmentPolicy** - Department management
2. **KpiEntryPolicy** - KPI entry access control
3. **CapaAreaPolicy** - CAPA area access control
4. **CapaActionPlanPolicy** - Action plan access control

### Policy Methods

```php
// In controller
$this->authorize('view', $kpiEntry);
$this->authorize('update', $kpiEntry);
$this->authorize('delete', $kpiEntry);
$this->authorize('lock', $kpiEntry); // Custom method

// In blade
@can('view', $kpiEntry)
    <a href="{{ route('kpi.show', $kpiEntry) }}">View</a>
@endcan

@can('update', $kpiEntry)
    <button>Edit</button>
@endcan

// In code
if (auth()->user()->can('update', $kpiEntry)) {
    // Update logic
}
```

---

## Testing

### Test User Permissions

```php
php artisan tinker
```

```php
// Get user
$user = User::find(1);

// Check role
$user->hasRole('admin'); // true/false
$user->hasAnyRole(['admin', 'manager']); // true/false

// Check permission
$user->can('edit kpi'); // true/false
$user->hasPermissionTo('edit kpi'); // true/false

// Get all permissions
$user->getAllPermissions();

// Get all roles
$user->getRoleNames(); // Collection

// Check department access
$user->canAccessDepartment(1); // true/false
$user->isAdmin(); // true/false
```

---

### Assign/Remove Roles and Permissions

```php
// Assign role
$user->assignRole('manager');
$user->assignRole(['manager', 'user']); // Multiple

// Remove role
$user->removeRole('user');

// Sync roles (replace all)
$user->syncRoles(['manager']);

// Give permission directly
$user->givePermissionTo('edit kpi');

// Revoke permission
$user->revokePermissionTo('edit kpi');

// Check and assign
if (!$user->hasRole('admin')) {
    $user->assignRole('admin');
}
```

---

## Database Structure

### Spatie Permission Tables

```
roles
├── id
├── name
├── guard_name
└── timestamps

permissions
├── id
├── name
├── guard_name
└── timestamps

model_has_roles (pivot)
├── role_id
├── model_type
└── model_id

model_has_permissions (pivot)
├── permission_id
├── model_type
└── model_id

role_has_permissions (pivot)
├── permission_id
└── role_id
```

### Custom Fields in users table

```
users
├── id
├── name
├── email
├── password
├── department_id (FK → departments)
├── can_access_all_departments (boolean)
└── timestamps
```

---

## Best Practices

### 1. Always Use Policies in Controllers

```php
// ✅ GOOD
$this->authorize('update', $kpiEntry);

// ❌ BAD - Don't mix policy and permission checks
if (auth()->user()->can('edit kpi')) {
    // Logic should be in policy
}
```

---

### 2. Department Filtering in Queries

```php
// ✅ GOOD - Use scope
$entries = KpiEntry::when(!auth()->user()->isAdmin(), function ($query) {
    $query->where('department_id', auth()->user()->department_id);
})->get();

// ❌ BAD - Manual filtering everywhere
if (!auth()->user()->isAdmin()) {
    $entries = KpiEntry::where('department_id', auth()->user()->department_id)->get();
} else {
    $entries = KpiEntry::all();
}
```

---

### 3. Use Middleware for Route Protection

```php
// ✅ GOOD
Route::middleware(['auth:sanctum', 'department.access', 'role:manager'])->group(function () {
    Route::resource('kpi-entries', KpiEntryController::class);
});

// ❌ BAD - Manual checks in every controller method
public function index() {
    if (!auth()->user()->hasRole('manager')) {
        abort(403);
    }
    // ...
}
```

---

### 4. Cache Permissions

Spatie automatically caches permissions. Clear cache after changes:

```bash
php artisan permission:cache-reset
```

Or in code:
```php
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

---

## Troubleshooting

### Issue: Permission not working after seeding

**Solution:** Clear permission cache
```bash
php artisan permission:cache-reset
```

---

### Issue: User can't access their own department

**Solution:** Check if `department_id` is set correctly
```php
$user->department_id; // Should not be null
$user->department; // Should return Department model
```

---

### Issue: Middleware blocking all requests

**Solution:** Check middleware order in routes
```php
// ✅ Correct order
Route::middleware(['auth:sanctum', 'department.access'])->group(...);

// ❌ Wrong - auth must come first
Route::middleware(['department.access', 'auth:sanctum'])->group(...);
```

---

### Issue: Policy not being called

**Solution:** Ensure policy is registered in AuthServiceProvider
```php
protected $policies = [
    KpiEntry::class => KpiEntryPolicy::class,
];
```

---

## Summary

✅ **Role-based access control** with 3 roles (admin, manager, user)  
✅ **30+ permissions** across all modules  
✅ **Department-level security** - users can only access their department  
✅ **Admin override** - admins can access everything  
✅ **Fine-grained policies** for model-level authorization  
✅ **Middleware protection** for routes  
✅ **Flexible permission system** - easy to extend  

This system provides enterprise-grade security for your KPI & CAPA reporting system with clear separation of concerns and department-level data isolation.
