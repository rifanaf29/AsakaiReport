# AsakaiReport - KPI & CAPA Reporting System

Enterprise-grade Laravel-based internal reporting system for KPI tracking and CAPA (Corrective and Preventive Action) management with role-based access control and department-level security.

---

## 🎯 Features

### Core Modules
- **KPI Module** - Department-level daily KPI tracking with dynamic fields
- **CAPA Module** - Hierarchical problem management (Area → Problem → Cause → Action Plan)

### Security & Access Control
- **Role-Based Access Control (RBAC)** - 3 roles with 30+ granular permissions
- **Department Isolation** - Users can only access their assigned department
- **Admin Override** - System administrators can access all departments
- **Policy-Based Authorization** - Fine-grained access control at model level

### Technical Features
- **Dynamic KPI Fields** - Flexible field configuration per template
- **Hybrid Data Storage** - Relational + JSON for optimal performance
- **Soft Deletes** - Full audit trail with data recovery
- **Business Rule Enforcement** - Automatic CAPA requirement when KPI status is NG
- **Performance Optimized** - Strategic indexing for thousands of records
- **Scalable Architecture** - Ready for enterprise deployment

---

## 📋 System Requirements

- PHP 8.1+
- MySQL 8.0+ or PostgreSQL 13+
- Composer
- Laravel 10.x
- Node.js & NPM (for frontend assets)

---

## 🚀 Quick Start

### 1. Installation
```bash
# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate
```

### 2. Configure Database
Edit `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=asakai_report
DB_USERNAME=root
DB_PASSWORD=
```

### 3. Install RBAC System
```bash
# Install Spatie Permission
composer require spatie/laravel-permission

# Publish configuration
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
```

### 4. Database Setup
```bash
# Run all migrations
php artisan migrate

# Seed roles and permissions
php artisan db:seed --class=RolesAndPermissionsSeeder

# (Optional) Seed sample departments
php artisan db:seed --class=DepartmentSeeder
```

### 5. Create Admin User
```bash
php artisan tinker
```
```php
$dept = App\Models\Department::create(['code' => 'ADMIN', 'name' => 'Administration', 'is_active' => true]);
$admin = App\Models\User::create([
    'name' => 'System Admin',
    'email' => 'admin@asakai.com',
    'password' => bcrypt('Admin@123'),
    'department_id' => $dept->id,
    'can_access_all_departments' => true,
    'email_verified_at' => now(),
]);
$admin->assignRole('admin');
echo "✓ Admin created: admin@asakai.com / Admin@123\n";
```

### 6. Run Development Server
```bash
php artisan serve
# Visit: http://localhost:8000
```

---

## 📖 Documentation

| Document | Description |
|----------|-------------|
| [DATABASE_ARCHITECTURE.md](DATABASE_ARCHITECTURE.md) | Complete database schema, ERD, and design decisions |
| [RBAC_DOCUMENTATION.md](RBAC_DOCUMENTATION.md) | Complete RBAC guide with usage examples |
| [RBAC_SETUP_GUIDE.md](RBAC_SETUP_GUIDE.md) | Step-by-step RBAC installation instructions |
| [RBAC_IMPLEMENTATION_SUMMARY.md](RBAC_IMPLEMENTATION_SUMMARY.md) | Quick reference for RBAC features |
| [SETUP_GUIDE.md](SETUP_GUIDE.md) | General setup and usage guide |

---

## 🔐 Roles & Permissions

### Role Hierarchy

```
ADMIN (Full Access)
  ↓
MANAGER (Department Management)
  ↓
USER (Basic Access)
```

### Role Comparison

| Feature | Admin | Manager | User |
|---------|-------|---------|------|
| Access all departments | ✅ | ❌ | ❌ |
| Create departments | ✅ | ❌ | ❌ |
| Delete KPI/CAPA | ✅ | ✅ | ❌ |
| Lock KPI entries | ✅ | ✅ | ❌ |
| Assign roles | ✅ | ❌ | ❌ |
| Manage users | ✅ | ✅ (dept) | ❌ |
| Export reports | ✅ | ✅ | ❌ |

**Complete permission matrix:** See [RBAC_DOCUMENTATION.md](RBAC_DOCUMENTATION.md#complete-permission-list)

---

## 🏗️ Architecture Overview

### Database Schema

```
┌─────────────────┐
│  departments    │
└────────┬────────┘
         │
         ├─────────────────────────┬──────────────────┐
         │                         │                  │
         ▼                         ▼                  ▼
┌─────────────────┐      ┌─────────────────┐   ┌──────────┐
│  kpi_templates  │      │   kpi_entries   │   │  users   │
└────────┬────────┘      └────────┬────────┘   └──────────┘
         │                        │
         ├──────────┐             │ (optional link)
         │          │             │
         ▼          │             ▼
┌────────────┐      │    ┌─────────────────┐
│kpi_template│      └───→│   capa_areas    │
│  _fields   │           └────────┬────────┘
└────────────┘                    │
                                  ▼
                         ┌─────────────────┐
                         │  capa_problems  │
                         └────────┬────────┘
                                  │
                                  ▼
                         ┌─────────────────┐
                         │   capa_causes   │
                         └────────┬────────┘
                                  │
                                  ▼
                         ┌──────────────────┐
                         │capa_action_plans │
                         └──────────────────┘
```

### Key Design Decisions

1. **Hybrid Storage**
   - Core fields (Date, Target, Actual, Status) → Database columns (indexed)
   - Dynamic fields (PD1-5) → JSON column (flexible)

2. **Relational CAPA**
   - Full hierarchical structure for powerful querying
   - Supports complex reporting and analytics

3. **Department Security**
   - Enforced at database level (foreign keys)
   - Validated by policies (authorization)
   - Protected by middleware (HTTP layer)

4. **Business Rules**
   - KPI Status = NG → CAPA entry required
   - Enforced via observers and validation

**Detailed architecture:** [DATABASE_ARCHITECTURE.md](DATABASE_ARCHITECTURE.md)

---

## 💻 Usage Examples

### Creating KPI Entry

```php
use App\Models\KpiEntry;

$kpiEntry = KpiEntry::create([
    'kpi_template_id' => 1,
    'department_id' => auth()->user()->department_id,
    'entry_date' => today(),
    'target' => 95.00,
    'actual' => 92.00,
    'status' => 'NG', // Below target
    'dynamic_fields' => [
        'pd1' => 93.0,
        'pd2' => 91.5,
        'pd3' => 94.0,
        'pd4' => 92.0,
        'pd5' => 92.0,
    ],
    'created_by' => auth()->id(),
]);

// Check if CAPA is required
if ($kpiEntry->requiresCapa()) {
    // Redirect to CAPA form
}
```

### Creating CAPA with Full Hierarchy

```php
use App\Models\CapaArea;

// Create CAPA Area
$capaArea = CapaArea::create([
    'department_id' => auth()->user()->department_id,
    'kpi_entry_id' => $kpiEntry->id,
    'capa_date' => today(),
    'area_name' => 'Production Line 3',
    'is_mandatory' => true,
    'created_by' => auth()->id(),
]);

// Create Problem
$problem = $capaArea->problems()->create([
    'problem_description' => 'High defect rate in welding',
    'severity' => 'high',
    'created_by' => auth()->id(),
]);

// Create Cause
$cause = $problem->causes()->create([
    'cause_description' => 'Temperature unstable',
    'cause_type' => 'Machine',
    'created_by' => auth()->id(),
]);

// Create Action Plan
$actionPlan = $cause->actionPlans()->create([
    'description' => 'Replace temperature sensor',
    'pic_user_id' => 10,
    'due_date' => now()->addDays(7),
    'status' => 'open',
    'created_by' => auth()->id(),
]);
```

### Checking Permissions

```php
// In controller
public function update(Request $request, KpiEntry $kpiEntry)
{
    // Check authorization
    $this->authorize('update', $kpiEntry);
    
    // Update entry
    $kpiEntry->update($request->validated());
}

// In Blade
@can('edit', $kpiEntry)
    <button>Edit</button>
@endcan

@role('admin')
    <button>Delete Department</button>
@endrole
```

### Department Filtering

```php
// Get KPI entries accessible by current user
$entries = KpiEntry::when(!auth()->user()->isAdmin(), function ($query) {
    $query->where('department_id', auth()->user()->department_id);
})->with('template', 'department')->paginate(50);

// Get overdue action plans in user's department
$overdueActions = CapaActionPlan::with('cause.problem.area')
    ->whereHas('cause.problem.area', function ($query) {
        if (!auth()->user()->isAdmin()) {
            $query->where('department_id', auth()->user()->department_id);
        }
    })
    ->overdue()
    ->get();
```

---

## 🛡️ Security Features

### 1. Department Isolation
```php
// Users can only access their department
$user->canAccessDepartment($departmentId); // true/false

// Admin override
$user->can_access_all_departments = true;
```

### 2. Policy-Based Authorization
```php
// Automatic authorization in controllers
$this->authorize('view', $kpiEntry);
$this->authorize('update', $kpiEntry);
$this->authorize('delete', $kpiEntry);
```

### 3. Middleware Protection
```php
// Protected routes
Route::middleware(['auth:sanctum', 'department.access'])->group(function () {
    Route::apiResource('kpi-entries', KpiEntryController::class);
});

// Role-based routes
Route::middleware('role:admin|manager')->group(function () {
    Route::delete('/kpi-entries/{kpiEntry}', [KpiController::class, 'destroy']);
});
```

### 4. Input Validation
```php
// Request validation
public function rules(): array
{
    return [
        'department_id' => ['required', 'exists:departments,id'],
        'status' => ['required', Rule::in(['OK', 'NG', 'PENDING'])],
        // ...
    ];
}
```

---

## 🧪 Testing

### Run Tests
```bash
php artisan test
```

### Manual Testing with Tinker
```bash
php artisan tinker
```

```php
// Test user permissions
$user = User::find(1);
$user->hasRole('admin'); // true/false
$user->can('edit kpi'); // true/false
$user->canAccessDepartment(1); // true/false

// Test KPI business rules
$kpiEntry = KpiEntry::find(1);
$kpiEntry->requiresCapa(); // true if status is NG
$kpiEntry->hasCapaFilled(); // true if CAPA exists

// Test CAPA completion
$capaArea = CapaArea::find(1);
$capaArea->getActionPlanCompletionPercentage(); // 0-100

// Test action plan status
$actionPlan = CapaActionPlan::find(1);
$actionPlan->isOverdue(); // true/false
$actionPlan->daysUntilDue(); // int (negative if overdue)
```

---

## 📊 Database Tables

### Core Tables
- `departments` - Department master data
- `users` - User accounts with department assignment

### KPI Module (4 tables)
- `kpi_templates` - KPI sheet templates
- `kpi_template_fields` - Dynamic field definitions
- `kpi_entries` - Daily KPI data entries

### CAPA Module (4 tables)
- `capa_areas` - CAPA top-level areas
- `capa_problems` - Problems within areas
- `capa_causes` - Root causes
- `capa_action_plans` - Corrective/preventive actions

### Spatie Permission (5 tables)
- `roles` - Role definitions
- `permissions` - Permission definitions
- `model_has_roles` - User-role assignments
- `model_has_permissions` - Direct user permissions
- `role_has_permissions` - Role-permission mappings

**Total:** 16 tables

---

## 🔧 Configuration

### Environment Variables
```env
# Application
APP_NAME=AsakaiReport
APP_ENV=production
APP_DEBUG=false

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=asakai_report

# Cache (recommended for production)
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Permissions
PERMISSION_CACHE_EXPIRATION_TIME=86400
```

### Permission Config
Edit `config/permission.php`:
```php
'cache' => [
    'expiration_time' => 86400, // 24 hours
    'key' => 'spatie.permission.cache',
],
```

---

## 🚀 Deployment

### Production Checklist
- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Run `php artisan config:cache`
- [ ] Run `php artisan route:cache`
- [ ] Run `php artisan view:cache`
- [ ] Set up database backups
- [ ] Configure Redis for caching
- [ ] Set up queue workers
- [ ] Configure rate limiting
- [ ] Enable HTTPS
- [ ] Set up monitoring (Laravel Telescope/Horizon)

### Performance Optimization
```bash
# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Cache permissions
php artisan permission:cache-reset
```

---

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This project is proprietary software. All rights reserved.

---

## 👥 Support

For support, email support@asakai.com or open an issue in the repository.

---

## 🎉 Acknowledgments

- **Laravel Framework** - https://laravel.com
- **Spatie Laravel Permission** - https://spatie.be/docs/laravel-permission
- **Tailwind CSS** - https://tailwindcss.com

---

## 📝 Changelog

### Version 1.0.0 (2026-02-14)
- ✅ Initial release
- ✅ KPI Module with dynamic fields
- ✅ CAPA Module with hierarchical structure
- ✅ Role-based access control (RBAC)
- ✅ Department-level security
- ✅ Comprehensive documentation
- ✅ Production-ready architecture

---

**Built with ❤️ for efficient KPI tracking and CAPA management**
