# Quick Setup - Role & Permission System

## Step-by-Step Installation

### 1. Install Spatie Permission Package

```bash
cd c:\Users\fajarsd\Downloads\AsakaiReport
composer require spatie/laravel-permission
```

### 2. Publish Spatie Configuration and Migrations

```bash
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

This creates:
- `config/permission.php` - Configuration file
- Database migrations for roles and permissions tables

### 3. Run All Migrations

```bash
# Run all migrations (including Spatie and custom department field)
php artisan migrate
```

This creates tables:
- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`
- Adds `department_id` and `can_access_all_departments` to `users` table

### 4. Seed Roles and Permissions

```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
```

**Output:**
```
✓ Created 3 roles: admin, manager, user
✓ Created 30+ permissions
✓ Assigned permissions to roles
```

### 5. Verify Installation

```bash
php artisan tinker
```

```php
// Check roles
\Spatie\Permission\Models\Role::all()->pluck('name');
// Should show: ["admin", "manager", "user"]

// Check permissions count
\Spatie\Permission\Models\Permission::count();
// Should show: 30+

// Check admin permissions
\Spatie\Permission\Models\Role::findByName('admin')->permissions->count();
// Should show: 30+ (all permissions)
```

---

## Create Your First Users

### Method 1: Using Seeders (Recommended)

The easiest way is to use the provided seeders that create sample departments and users.

```bash
# Run all seeders at once (recommended)
php artisan db:seed

# This will create:
# - 3 roles with permissions (admin, manager, user)
# - 8 sample departments (Production, QA, Logistics, etc.)
# - 11 sample users with different roles
```

**Created Users:**

**Admin Accounts (Full Access):**
- admin@asakai.com / Admin@123
- superadmin@asakai.com / Super@123

**Manager Accounts (Department Level):**
- manager.prod@asakai.com / Manager@123 (Production)
- manager.qa@asakai.com / Manager@123 (QA)
- manager.log@asakai.com / Manager@123 (Logistics)

**User Accounts (Basic Access):**
- john.doe@asakai.com / User@123 (Production)
- jane.smith@asakai.com / User@123 (Production)
- mike.johnson@asakai.com / User@123 (QA)
- sarah.williams@asakai.com / User@123 (QA)
- david.brown@asakai.com / User@123 (Logistics)
- emily.davis@asakai.com / User@123 (Logistics)

📖 **Complete user list:** See [SAMPLE_USERS.md](SAMPLE_USERS.md)

---

### Method 2: Using Tinker (Manual)

```bash
php artisan tinker
```

```php
use App\Models\User;
use App\Models\Department;

// 1. Create or get a department
$dept = Department::first();
// If no department exists:
// $dept = Department::create(['code' => 'PROD', 'name' => 'Production', 'is_active' => true]);

// 2. Create Admin User
$admin = User::create([
    'name' => 'System Admin',
    'email' => 'admin@asakai.com',
    'password' => bcrypt('Admin@123'),
    'department_id' => $dept->id,
    'can_access_all_departments' => true,
    'email_verified_at' => now(),
]);
$admin->assignRole('admin');

// 3. Create Manager User
$manager = User::create([
    'name' => 'Production Manager',
    'email' => 'manager@asakai.com',
    'password' => bcrypt('Manager@123'),
    'department_id' => $dept->id,
    'can_access_all_departments' => false,
    'email_verified_at' => now(),
]);
$manager->assignRole('manager');

// 4. Create Regular User
$user = User::create([
    'name' => 'Regular User',
    'email' => 'user@asakai.com',
    'password' => bcrypt('User@123'),
    'department_id' => $dept->id,
    'can_access_all_departments' => false,
    'email_verified_at' => now(),
]);
$user->assignRole('user');

echo "✓ Created 3 users successfully!\n";
echo "Admin: admin@asakai.com / Admin@123\n";
echo "Manager: manager@asakai.com / Manager@123\n";
echo "User: user@asakai.com / User@123\n";
```

---

### Method 3: Using Seeder (Best for Production Setup)

If you want to customize the users, edit the seeder:

**Edit:** `database/seeders/UserSeeder.php`

Then run:
```bash
php artisan db:seed --class=UserSeeder
```

Or run all seeders:
```bash
php artisan db:seed
```

This will seed:
1. ✅ Roles and permissions
2. ✅ Sample departments
3. ✅ Sample users with roles

---

## Seeder Order

The seeders run in this order (defined in `DatabaseSeeder.php`):

```
1. RolesAndPermissionsSeeder  → Creates roles & permissions
2. DepartmentSeeder           → Creates sample departments
3. UserSeeder                 → Creates users with roles
4. DashboardTableSeeder       → Other data (if needed)
```

Run individual seeders:
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=DepartmentSeeder
php artisan db:seed --class=UserSeeder
```

---

## Test User Permissions

```bash
php artisan tinker
```

```php
use App\Models\User;

// Get admin user
$admin = User::role('admin')->first();

// Test admin permissions
$admin->hasRole('admin'); // true
$admin->can('delete departments'); // true
$admin->can('view all department kpi'); // true
$admin->isAdmin(); // true

// Get manager user
$manager = User::role('manager')->first();

// Test manager permissions
$manager->hasRole('manager'); // true
$manager->can('edit kpi'); // true
$manager->can('delete departments'); // false (admin only)
$manager->isManager(); // true

// Get regular user
$user = User::role('user')->first();

// Test user permissions
$user->hasRole('user'); // true
$user->can('view kpi'); // true
$user->can('delete kpi'); // false
$user->isUser(); // true

// Test department access
$user->canAccessDepartment($user->department_id); // true
$user->canAccessDepartment(999); // false (different department)
```

---

## Configure Routes with Protection

Update `routes/api.php`:

```php
use App\Http\Controllers\Api\KpiEntryController;
use App\Http\Controllers\Api\CapaAreaController;
use App\Http\Controllers\Api\DepartmentController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes: authenticated + department access check
Route::middleware(['auth:sanctum', 'department.access'])->group(function () {
    
    // Dashboard - all authenticated users
    Route::get('/dashboard', [DashboardController::class, 'index']);
    
    // KPI Routes
    Route::prefix('kpi')->group(function () {
        Route::get('/', [KpiEntryController::class, 'index'])->middleware('permission:view kpi');
        Route::post('/', [KpiEntryController::class, 'store'])->middleware('permission:create kpi');
        Route::get('/{kpiEntry}', [KpiEntryController::class, 'show'])->middleware('permission:view kpi');
        Route::put('/{kpiEntry}', [KpiEntryController::class, 'update'])->middleware('permission:edit kpi');
        Route::delete('/{kpiEntry}', [KpiEntryController::class, 'destroy'])->middleware('permission:delete kpi');
        Route::post('/{kpiEntry}/lock', [KpiEntryController::class, 'lock'])->middleware('permission:lock kpi');
    });
    
    // CAPA Routes
    Route::prefix('capa')->group(function () {
        Route::get('/', [CapaAreaController::class, 'index'])->middleware('permission:view capa');
        Route::post('/', [CapaAreaController::class, 'store'])->middleware('permission:create capa');
        Route::get('/{capaArea}', [CapaAreaController::class, 'show'])->middleware('permission:view capa');
        Route::put('/{capaArea}', [CapaAreaController::class, 'update'])->middleware('permission:edit capa');
        Route::delete('/{capaArea}', [CapaAreaController::class, 'destroy'])->middleware('permission:delete capa');
    });
    
    // Department Routes - Admin only
    Route::middleware('role:admin')->group(function () {
        Route::apiResource('departments', DepartmentController::class);
    });
    
    // User management - Admin and Manager
    Route::middleware('role:admin|manager')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->middleware('permission:view users');
        Route::post('/users', [UserController::class, 'store'])->middleware('permission:create users');
    });
});
```

---

## Verify Middleware is Working

Create a test controller:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function checkAuth(Request $request)
    {
        $user = $request->user();
        
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department->name,
                'roles' => $user->getRoleNames(),
                'can_access_all_departments' => $user->can_access_all_departments,
            ],
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'checks' => [
                'is_admin' => $user->isAdmin(),
                'is_manager' => $user->isManager(),
                'is_user' => $user->isUser(),
                'can_edit_kpi' => $user->can('edit kpi'),
                'can_delete_departments' => $user->can('delete departments'),
            ],
        ]);
    }
}
```

Add route:
```php
Route::middleware('auth:sanctum')->get('/test/auth', [TestController::class, 'checkAuth']);
```

Test with API client (Postman, Insomnia):
```
GET /api/test/auth
Authorization: Bearer {token}
```

---

## Common Tasks

### Give User Additional Permission

```php
$user = User::find(1);
$user->givePermissionTo('export reports');
```

### Change User Role

```php
$user = User::find(1);
$user->syncRoles(['manager']); // Replace all roles with 'manager'
```

### Allow User to Access All Departments

```php
$user = User::find(1);
$user->update(['can_access_all_departments' => true]);
```

### Create Custom Permission

```php
use Spatie\Permission\Models\Permission;

Permission::create(['name' => 'approve reports']);

// Give to admin role
$admin = \Spatie\Permission\Models\Role::findByName('admin');
$admin->givePermissionTo('approve reports');
```

---

## Clear Permission Cache

After making changes to permissions or roles, clear the cache:

```bash
php artisan permission:cache-reset
```

Or in code:
```php
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

---

## Complete Installation Checklist

- [ ] Install `spatie/laravel-permission` via composer
- [ ] Publish Spatie configuration and migrations
- [ ] Run `php artisan migrate`
- [ ] Run `RolesAndPermissionsSeeder`
- [ ] Create test users with roles
- [ ] Update `app/Http/Kernel.php` with middleware aliases (already done)
- [ ] Update `app/Providers/AuthServiceProvider.php` with policies (already done)
- [ ] Configure routes with middleware protection
- [ ] Test authentication and authorization
- [ ] Clear permission cache

---

## Files Created

✅ **Migration:** `2024_01_02_000001_add_department_to_users_table.php`  
✅ **Seeder:** `RolesAndPermissionsSeeder.php`  
✅ **Middleware:** `CheckDepartmentAccess.php`  
✅ **Policies:** `DepartmentPolicy`, `KpiEntryPolicy`, `CapaAreaPolicy`, `CapaActionPlanPolicy`  
✅ **Model:** Updated `User.php` with HasRoles trait  
✅ **Kernel:** Registered middleware aliases  
✅ **AuthServiceProvider:** Registered policies  

---

## Next Steps

1. **Implement authentication endpoints** (login, logout, register)
2. **Add API token generation** (Sanctum tokens)
3. **Create frontend role-based UI** (show/hide elements based on permissions)
4. **Add audit logging** (track who did what)
5. **Implement password policies** (strength requirements)
6. **Set up 2FA** (two-factor authentication)

For detailed usage examples and troubleshooting, see [RBAC_DOCUMENTATION.md](RBAC_DOCUMENTATION.md).
