# Laravel KPI & CAPA Reporting System - Database Architecture

## Overview
This document outlines the database architecture for a scalable Laravel-based internal reporting system with two main modules: **KPI Module** (Department-Level Daily Data) and **CAPA Module** (Problem & Action Plan Management).

---

## Table of Contents
1. [Database Schema Design](#database-schema-design)
2. [Entity Relationship Diagram](#entity-relationship-diagram)
3. [Design Decisions](#design-decisions)
4. [Laravel Eloquent Relationships](#laravel-eloquent-relationships)
5. [Business Rules Implementation](#business-rules-implementation)
6. [Performance Optimization](#performance-optimization)
7. [Scalability Considerations](#scalability-considerations)
8. [Usage Examples](#usage-examples)

---

## Database Schema Design

### KPI Module Tables

#### 1. `departments`
Stores department information.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| code | varchar(50) | Unique department code (e.g., PROD, QA) |
| name | varchar(100) | Department name |
| description | text | Department description |
| is_active | boolean | Active status |
| timestamps | timestamp | Created/Updated timestamps |
| deleted_at | timestamp | Soft delete |

**Indexes:**
- Unique: `code`
- Index: `is_active`

---

#### 2. `kpi_templates`
Stores KPI sheet templates (like Excel sheets A, B, etc.).

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| department_id | bigint | Foreign key to departments |
| name | varchar(100) | Template name (e.g., "Sheet A") |
| code | varchar(50) | Unique template code |
| description | text | Template description |
| target_unit | varchar(20) | Default unit (%, Day, pcs) |
| is_active | boolean | Active status |
| sort_order | integer | Display order |
| timestamps | timestamp | Created/Updated timestamps |
| deleted_at | timestamp | Soft delete |

**Indexes:**
- Unique: `(department_id, code)`
- Index: `is_active`

**Relationships:**
- Belongs to: Department
- Has many: KpiTemplateFields, KpiEntries

---

#### 3. `kpi_template_fields`
Stores dynamic fields per template (PD1, PD2, PD3, etc.).

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| kpi_template_id | bigint | Foreign key to kpi_templates |
| field_name | varchar(100) | Display name (e.g., "PD1") |
| field_key | varchar(50) | Storage key (e.g., "pd1") |
| field_type | enum | text, number, decimal, date, calculated |
| is_required | boolean | Is field required |
| is_editable | boolean | Is field editable |
| calculation_formula | text | For calculated fields |
| unit | varchar(20) | Field unit |
| sort_order | integer | Display order |
| timestamps | timestamp | Created/Updated timestamps |

**Indexes:**
- Unique: `(kpi_template_id, field_key)`
- Index: `kpi_template_id`

**Relationships:**
- Belongs to: KpiTemplate

---

#### 4. `kpi_entries`
Stores daily KPI data entries (CORE TABLE).

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| kpi_template_id | bigint | Foreign key to kpi_templates |
| department_id | bigint | Foreign key to departments |
| entry_date | date | Date of KPI entry |
| target | decimal(10,2) | Target value (ALWAYS PRESENT) |
| actual | decimal(10,2) | Actual value (ALWAYS PRESENT) |
| status | enum | OK, NG, PENDING (ALWAYS PRESENT) |
| dynamic_fields | json | Dynamic fields (PD1-5, etc.) |
| created_by | bigint | Foreign key to users |
| updated_by | bigint | Foreign key to users |
| notes | text | Additional notes |
| is_locked | boolean | Lock editing after period |
| timestamps | timestamp | Created/Updated timestamps |
| deleted_at | timestamp | Soft delete |

**Indexes:**
- Unique: `(kpi_template_id, department_id, entry_date)` - One entry per template per department per day
- Index: `entry_date`
- Index: `status`
- Index: `(department_id, entry_date)` - For dashboard queries
- Index: `created_at`

**Relationships:**
- Belongs to: KpiTemplate, Department, Creator (User), Updater (User)
- Has many: CapaAreas

---

### CAPA Module Tables

#### 5. `capa_areas`
Top-level CAPA entity representing an area on a specific date.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| department_id | bigint | Foreign key to departments |
| kpi_entry_id | bigint | Foreign key to kpi_entries (nullable) |
| capa_date | date | Date of CAPA |
| area_name | varchar(100) | Area name (e.g., "Line 1") |
| area_description | text | Area description |
| is_mandatory | boolean | True if linked to NG KPI |
| created_by | bigint | Foreign key to users |
| timestamps | timestamp | Created/Updated timestamps |
| deleted_at | timestamp | Soft delete |

**Indexes:**
- Index: `capa_date`
- Index: `(department_id, capa_date)`
- Index: `kpi_entry_id`
- Index: `is_mandatory`

**Relationships:**
- Belongs to: Department, KpiEntry (optional), Creator (User)
- Has many: CapaProblems

---

#### 6. `capa_problems`
Problems identified within a CAPA area.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| capa_area_id | bigint | Foreign key to capa_areas |
| problem_description | text | Problem description |
| problem_category | varchar(50) | Category (Quality, Safety, etc.) |
| severity | enum | low, medium, high, critical |
| sort_order | integer | Display order |
| created_by | bigint | Foreign key to users |
| timestamps | timestamp | Created/Updated timestamps |
| deleted_at | timestamp | Soft delete |

**Indexes:**
- Index: `capa_area_id`
- Index: `problem_category`
- Index: `severity`

**Relationships:**
- Belongs to: CapaArea, Creator (User)
- Has many: CapaCauses

---

#### 7. `capa_causes`
Root causes of problems.

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| capa_problem_id | bigint | Foreign key to capa_problems |
| cause_description | text | Root cause description |
| cause_type | varchar(50) | Man, Machine, Method, Material, Environment |
| sort_order | integer | Display order |
| created_by | bigint | Foreign key to users |
| timestamps | timestamp | Created/Updated timestamps |
| deleted_at | timestamp | Soft delete |

**Indexes:**
- Index: `capa_problem_id`
- Index: `cause_type`

**Relationships:**
- Belongs to: CapaProblem, Creator (User)
- Has many: CapaActionPlans

---

#### 8. `capa_action_plans`
Action plans to address causes (LEAF NODE).

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| capa_cause_id | bigint | Foreign key to capa_causes |
| description | text | Action plan description |
| pic_user_id | bigint | Person In Charge (Foreign key to users) |
| due_date | date | Target completion date |
| keterangan | text | Additional notes/remarks |
| status | enum | open, progress, close |
| completed_date | date | Actual completion date |
| progress_percentage | integer | 0-100% |
| completion_notes | text | Notes on completion |
| sort_order | integer | Display order |
| created_by | bigint | Foreign key to users |
| updated_by | bigint | Foreign key to users |
| timestamps | timestamp | Created/Updated timestamps |
| deleted_at | timestamp | Soft delete |

**Indexes:**
- Index: `capa_cause_id`
- Index: `pic_user_id`
- Index: `status`
- Index: `due_date`
- Compound Index: `(status, due_date)` - For overdue tracking

**Relationships:**
- Belongs to: CapaCause, PIC (User), Creator (User), Updater (User)

---

## Entity Relationship Diagram

```
┌─────────────────┐
│  departments    │
└────────┬────────┘
         │
         ├─────────────────────────────────────┐
         │                                     │
         ▼                                     ▼
┌─────────────────┐                  ┌─────────────────┐
│  kpi_templates  │                  │   capa_areas    │
└────────┬────────┘                  └────────┬────────┘
         │                                     │
         ├──────────┬──────────┐              │
         │          │          │              │
         ▼          ▼          │              ▼
┌────────────┐ ┌────────────┐ │     ┌─────────────────┐
│kpi_template│ │ kpi_entries│◄┘     │  capa_problems  │
│  _fields   │ └────────────┘       └────────┬────────┘
└────────────┘                               │
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

**Key Relationships:**
- One Department → Many KpiTemplates
- One KpiTemplate → Many KpiEntries
- One KpiTemplate → Many KpiTemplateFields
- One Department → Many CapaAreas
- One KpiEntry → Many CapaAreas (optional link)
- One CapaArea → Many CapaProblems
- One CapaProblem → Many CapaCauses
- One CapaCause → Many CapaActionPlans

---

## Design Decisions

### 1. **Hybrid Approach for Dynamic KPI Fields**

**Decision:** Store common fields (Date, Target, Actual, Status) as database columns, and dynamic fields (PD1-PD5, etc.) as JSON.

**Rationale:**
- ✅ Common fields are indexed for fast queries and aggregations
- ✅ Dynamic fields are flexible per template without schema changes
- ✅ JSON field support in MySQL 5.7+, PostgreSQL 9.4+ is mature
- ✅ Laravel's native JSON casting makes it easy to work with

**Alternative Considered:** Full EAV (Entity-Attribute-Value) pattern
- ❌ More complex queries
- ❌ More tables and joins
- ❌ Harder to maintain

**When to Query JSON:**
```sql
-- MySQL 8.0+
SELECT * FROM kpi_entries 
WHERE JSON_EXTRACT(dynamic_fields, '$.pd1') > 80;

-- PostgreSQL
SELECT * FROM kpi_entries 
WHERE (dynamic_fields->>'pd1')::numeric > 80;
```

---

### 2. **Relational Structure for CAPA (Not JSON)**

**Decision:** Use proper relational tables for CAPA hierarchy instead of nested JSON.

**Rationale:**
- ✅ Need to query by status, PIC, due date
- ✅ Need referential integrity
- ✅ Need to track history and changes
- ✅ Better for reporting and aggregations
- ✅ Easier to implement cascade deletes and soft deletes

**Alternative Considered:** JSON hierarchy
- ❌ Difficult to query nested structures
- ❌ No referential integrity
- ❌ Harder to update specific action plans
- ❌ Poor performance for reports

---

### 3. **Soft Deletes Everywhere**

**Decision:** Use `deleted_at` (soft deletes) on all major tables.

**Rationale:**
- ✅ Audit trail for compliance
- ✅ Can restore accidentally deleted data
- ✅ Historical reporting remains accurate
- ✅ Laravel's `SoftDeletes` trait makes it easy

---

### 4. **Composite Unique Index on KPI Entries**

**Decision:** Unique constraint on `(kpi_template_id, department_id, entry_date)`.

**Rationale:**
- ✅ Prevents duplicate entries for same template/department/date
- ✅ Enforces business rule at database level
- ✅ Faster queries when searching by these fields

---

### 5. **Optional Link Between KPI and CAPA**

**Decision:** `capa_areas.kpi_entry_id` is nullable.

**Rationale:**
- ✅ CAPA can exist independently (not from NG KPI)
- ✅ CAPA can be mandatory (linked to KPI) or voluntary
- ✅ Flexible for future use cases

---

### 6. **Separate User Tracking**

**Decision:** Track `created_by`, `updated_by`, `pic_user_id` separately.

**Rationale:**
- ✅ Know who created vs who last modified
- ✅ Know who is responsible (PIC) for action plans
- ✅ Better audit trail
- ✅ Useful for permission checks

---

## Laravel Eloquent Relationships

### KPI Module Relationships

```php
// Department.php
public function kpiTemplates() // HasMany
public function activeKpiTemplates() // HasMany with scope
public function kpiEntries() // HasMany
public function capaAreas() // HasMany

// KpiTemplate.php
public function department() // BelongsTo
public function fields() // HasMany
public function entries() // HasMany

// KpiEntry.php
public function template() // BelongsTo
public function department() // BelongsTo
public function creator() // BelongsTo User
public function updater() // BelongsTo User
public function capaAreas() // HasMany
```

### CAPA Module Relationships

```php
// CapaArea.php
public function department() // BelongsTo
public function kpiEntry() // BelongsTo (nullable)
public function creator() // BelongsTo User
public function problems() // HasMany
public function causes() // HasManyThrough CapaProblem

// CapaProblem.php
public function area() // BelongsTo CapaArea
public function creator() // BelongsTo User
public function causes() // HasMany

// CapaCause.php
public function problem() // BelongsTo CapaProblem
public function creator() // BelongsTo User
public function actionPlans() // HasMany

// CapaActionPlan.php
public function cause() // BelongsTo CapaCause
public function pic() // BelongsTo User
public function creator() // BelongsTo User
public function updater() // BelongsTo User
```

---

## Business Rules Implementation

### Rule 1: If KPI Status = NG, CAPA is Required

**Implementation Approaches:**

#### A. Model Observer (Recommended)
```php
// app/Observers/KpiEntryObserver.php
public function saving(KpiEntry $kpiEntry): void
{
    if ($kpiEntry->isDirty('status') && $kpiEntry->status === 'NG') {
        $kpiEntry->_requires_capa_validation = true;
    }
}

public function saved(KpiEntry $kpiEntry): void
{
    if (isset($kpiEntry->_requires_capa_validation)) {
        // Log warning or trigger notification
        if (!$kpiEntry->hasCapaFilled()) {
            event(new CapaRequiredEvent($kpiEntry));
        }
    }
}
```

Register in `AppServiceProvider`:
```php
use App\Models\KpiEntry;
use App\Observers\KpiEntryObserver;

public function boot()
{
    KpiEntry::observe(KpiEntryObserver::class);
}
```

#### B. Form Validation
```php
// app/Http/Requests/StoreKpiEntryRequest.php
public function withValidator($validator)
{
    $validator->after(function ($validator) {
        if ($this->status === 'NG' && !$this->has('capa_area_id')) {
            $validator->errors()->add('status', 
                'CAPA must be filled when status is NG.');
        }
    });
}
```

#### C. Database Trigger (Not recommended for Laravel)
Use Laravel instead of raw triggers for maintainability.

---

### Rule 2: Status Validation

```php
// In KpiEntry model
const STATUS_OK = 'OK';
const STATUS_NG = 'NG';
const STATUS_PENDING = 'PENDING';

public function requiresCapa(): bool
{
    return $this->status === self::STATUS_NG;
}

public function hasCapaFilled(): bool
{
    return $this->capaAreas()->where('is_mandatory', true)->exists();
}
```

---

## Performance Optimization

### 1. **Indexes Strategy**

**Already Implemented:**
- Primary keys on all tables (auto-indexed)
- Foreign keys (partially indexed)
- Composite unique index on `kpi_entries`
- Date indexes for range queries
- Status indexes for filtering
- Compound index `(status, due_date)` for overdue tracking

**Recommendation for Large Scale:**
```sql
-- For dashboard queries (monthly aggregations)
CREATE INDEX idx_kpi_entries_date_status 
ON kpi_entries(entry_date, status);

-- For CAPA reporting
CREATE INDEX idx_capa_action_plans_pic_status 
ON capa_action_plans(pic_user_id, status, due_date);
```

---

### 2. **Eager Loading**

Always use eager loading to avoid N+1 query problems:

```php
// BAD: N+1 queries
$kpiEntries = KpiEntry::all();
foreach ($kpiEntries as $entry) {
    echo $entry->department->name; // N queries
}

// GOOD: 2 queries
$kpiEntries = KpiEntry::with('department', 'template')->get();
foreach ($kpiEntries as $entry) {
    echo $entry->department->name;
}
```

**For Complex CAPA Hierarchy:**
```php
$capaAreas = CapaArea::with([
    'department',
    'problems.causes.actionPlans.pic'
])->get();
```

---

### 3. **Database Query Optimization**

```php
// Use select() to limit columns
KpiEntry::select('id', 'entry_date', 'status', 'target', 'actual')
    ->where('department_id', 1)
    ->get();

// Use chunk() for large datasets
KpiEntry::where('entry_date', '>=', '2024-01-01')
    ->chunk(1000, function ($entries) {
        // Process 1000 records at a time
    });
```

---

### 4. **Caching Strategy**

```php
// Cache department KPI summary for 1 hour
$summary = Cache::remember("kpi_summary_dept_{$deptId}", 3600, function () use ($deptId) {
    return KpiEntry::where('department_id', $deptId)
        ->whereMonth('entry_date', now()->month)
        ->selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "OK" THEN 1 ELSE 0 END) as ok_count,
            SUM(CASE WHEN status = "NG" THEN 1 ELSE 0 END) as ng_count
        ')
        ->first();
});
```

---

### 5. **Aggregation Tables (Optional)**

For very large datasets (millions of records), consider aggregation tables:

```php
// Example aggregation table (create via migration)
Schema::create('kpi_monthly_summaries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('department_id');
    $table->foreignId('kpi_template_id');
    $table->date('month_start');
    $table->integer('total_entries');
    $table->integer('ok_count');
    $table->integer('ng_count');
    $table->decimal('avg_achievement', 5, 2);
    $table->timestamps();
    
    $table->unique(['department_id', 'kpi_template_id', 'month_start']);
});
```

Update via scheduled command:
```php
// app/Console/Commands/AggregateKpiData.php
php artisan kpi:aggregate --month=2024-01
```

---

## Scalability Considerations

### 1. **Horizontal Partitioning (Sharding)**

When reaching millions of records, consider:
- **Date-based partitioning** on `kpi_entries` table by year/month
- **Department-based sharding** for multi-tenant setup

```sql
-- MySQL 8.0+ Partitioning
ALTER TABLE kpi_entries
PARTITION BY RANGE (YEAR(entry_date)) (
    PARTITION p2024 VALUES LESS THAN (2025),
    PARTITION p2025 VALUES LESS THAN (2026),
    PARTITION p2026 VALUES LESS THAN (2027)
);
```

---

### 2. **Read Replicas**

For heavy reporting:
- Master DB: Write operations (KPI entry, CAPA updates)
- Read Replica: Dashboard queries, reports

Configure in `config/database.php`:
```php
'mysql' => [
    'write' => [
        'host' => env('DB_HOST'),
    ],
    'read' => [
        'host' => env('DB_HOST_REPLICA'),
    ],
    // ... other config
],
```

---

### 3. **Queue-Heavy Operations**

For tasks like:
- Sending notifications when status = NG
- Generating monthly reports
- Calculating complex aggregations

Use Laravel Queues:
```php
// Dispatch to queue
dispatch(new GenerateMonthlyKpiReport($departmentId, $month));
```

---

### 4. **API Rate Limiting**

For dashboard APIs:
```php
// routes/api.php
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/kpi/dashboard', [KpiController::class, 'dashboard']);
});
```

---

### 5. **Database Connection Pooling**

Use persistent connections in `config/database.php`:
```php
'options' => [
    PDO::ATTR_PERSISTENT => true,
],
```

---

## Usage Examples

### Example 1: Create Department with KPI Template

```php
// Create department
$department = Department::create([
    'code' => 'PROD',
    'name' => 'Production',
    'description' => 'Production Department',
    'is_active' => true,
]);

// Create KPI template (Sheet B from requirements)
$template = $department->kpiTemplates()->create([
    'name' => 'Production Quality Sheet',
    'code' => 'SHEET_PROD_QUALITY',
    'description' => 'Daily production quality tracking',
    'target_unit' => 'Day',
    'is_active' => true,
]);

// Create template fields
$template->fields()->createMany([
    ['field_name' => 'PD1', 'field_key' => 'pd1', 'field_type' => 'decimal', 'is_required' => true, 'sort_order' => 1],
    ['field_name' => 'PD2', 'field_key' => 'pd2', 'field_type' => 'decimal', 'is_required' => true, 'sort_order' => 2],
    ['field_name' => 'PD3', 'field_key' => 'pd3', 'field_type' => 'decimal', 'is_required' => true, 'sort_order' => 3],
    ['field_name' => 'PD4', 'field_key' => 'pd4', 'field_type' => 'decimal', 'is_required' => true, 'sort_order' => 4],
    ['field_name' => 'PD5', 'field_key' => 'pd5', 'field_type' => 'decimal', 'is_required' => true, 'sort_order' => 5],
]);
```

---

### Example 2: Create KPI Entry with Dynamic Fields

```php
$kpiEntry = KpiEntry::create([
    'kpi_template_id' => $template->id,
    'department_id' => $department->id,
    'entry_date' => '2024-02-14',
    'target' => 95.00,
    'actual' => 92.50,
    'status' => 'NG', // Below target
    'dynamic_fields' => [
        'pd1' => 93.0,
        'pd2' => 91.5,
        'pd3' => 94.0,
        'pd4' => 92.0,
        'pd5' => 92.0,
        // Actual = AVG(93.0, 91.5, 94.0, 92.0, 92.0) = 92.5
    ],
    'created_by' => auth()->id(),
    'notes' => 'Machine issue on Line 3',
]);

// Check if CAPA is required
if ($kpiEntry->requiresCapa()) {
    // Redirect to CAPA form or show warning
    return redirect()->route('capa.create', ['kpi_entry_id' => $kpiEntry->id]);
}
```

---

### Example 3: Create Full CAPA Hierarchy

```php
// Create CAPA Area (mandatory because linked to NG KPI)
$capaArea = CapaArea::create([
    'department_id' => $department->id,
    'kpi_entry_id' => $kpiEntry->id,
    'capa_date' => '2024-02-14',
    'area_name' => 'Production Line 3',
    'area_description' => 'Quality issue on assembly line',
    'is_mandatory' => true,
    'created_by' => auth()->id(),
]);

// Create Problem
$problem = $capaArea->problems()->create([
    'problem_description' => 'High defect rate in welding process',
    'problem_category' => 'Quality',
    'severity' => 'high',
    'created_by' => auth()->id(),
]);

// Create Cause
$cause = $problem->causes()->create([
    'cause_description' => 'Welding machine temperature unstable',
    'cause_type' => 'Machine',
    'created_by' => auth()->id(),
]);

// Create Action Plan
$actionPlan = $cause->actionPlans()->create([
    'description' => 'Replace temperature sensor and calibrate machine',
    'pic_user_id' => 10, // User ID of maintenance engineer
    'due_date' => '2024-02-20',
    'keterangan' => 'Coordinate with maintenance team',
    'status' => 'open',
    'progress_percentage' => 0,
    'created_by' => auth()->id(),
]);
```

---

### Example 4: Dashboard Query (Monthly KPI Summary)

```php
// Get KPI summary for department (current month)
$summary = KpiEntry::where('department_id', $departmentId)
    ->whereMonth('entry_date', now()->month)
    ->whereYear('entry_date', now()->year)
    ->selectRaw('
        COUNT(*) as total_entries,
        SUM(CASE WHEN status = "OK" THEN 1 ELSE 0 END) as ok_count,
        SUM(CASE WHEN status = "NG" THEN 1 ELSE 0 END) as ng_count,
        SUM(CASE WHEN status = "PENDING" THEN 1 ELSE 0 END) as pending_count,
        ROUND(AVG(actual), 2) as avg_actual,
        ROUND(AVG(target), 2) as avg_target,
        ROUND(AVG(actual / NULLIF(target, 0) * 100), 2) as achievement_rate
    ')
    ->first();

// Get overdue action plans
$overdueActions = CapaActionPlan::with('cause.problem.area', 'pic')
    ->whereHas('cause.problem.area', function ($query) use ($departmentId) {
        $query->where('department_id', $departmentId);
    })
    ->overdue()
    ->orderBy('due_date')
    ->get();
```

---

### Example 5: Generate Report with Eager Loading

```php
// Efficient query for KPI report with all relationships
$kpiData = KpiEntry::with([
    'department:id,name,code',
    'template:id,name,target_unit',
    'creator:id,name',
])
    ->dateRange('2024-02-01', '2024-02-29')
    ->orderBy('entry_date', 'desc')
    ->get();

// Efficient CAPA report
$capaReport = CapaArea::with([
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
])
    ->dateRange('2024-02-01', '2024-02-29')
    ->get();
```

---

### Example 6: Check Business Rules

```php
// In controller
public function storeKpiEntry(StoreKpiEntryRequest $request)
{
    $kpiEntry = KpiEntry::create($request->validated());

    // Business Rule Check
    if ($kpiEntry->requiresCapa() && !$kpiEntry->hasCapaFilled()) {
        return response()->json([
            'message' => 'KPI entry created successfully',
            'warning' => 'Status is NG. Please fill CAPA immediately.',
            'capa_required' => true,
            'kpi_entry_id' => $kpiEntry->id,
        ], 201);
    }

    return response()->json([
        'message' => 'KPI entry created successfully',
        'kpi_entry_id' => $kpiEntry->id,
    ], 201);
}
```

---

## Migration Commands

```bash
# Run all migrations
php artisan migrate

# Rollback last batch
php artisan migrate:rollback

# Fresh migration (WARNING: drops all tables)
php artisan migrate:fresh

# Run specific migration
php artisan migrate --path=/database/migrations/2024_01_01_000001_create_departments_table.php

# Check migration status
php artisan migrate:status
```

---

## Seeder Example

```php
// database/seeders/DepartmentKpiSeeder.php
public function run()
{
    $departments = [
        ['code' => 'PROD', 'name' => 'Production', 'description' => 'Production Department'],
        ['code' => 'QA', 'name' => 'Quality Assurance', 'description' => 'QA Department'],
        ['code' => 'LOG', 'name' => 'Logistics', 'description' => 'Logistics Department'],
    ];

    foreach ($departments as $dept) {
        Department::create($dept + ['is_active' => true]);
    }
}

// Run seeder
php artisan db:seed --class=DepartmentKpiSeeder
```

---

## Conclusion

This architecture provides:
- ✅ **Clean separation** between KPI and CAPA modules
- ✅ **Scalability** via proper indexing, caching, and query optimization
- ✅ **Flexibility** with JSON for dynamic fields and relational structure for hierarchy
- ✅ **Maintainability** with Laravel best practices (Eloquent, Observers, Validation)
- ✅ **Performance** optimized for thousands of records per month
- ✅ **Extensibility** for future enhancements

**Next Steps:**
1. Register `KpiEntryObserver` in `AppServiceProvider`
2. Create controllers for KPI and CAPA CRUD
3. Implement authorization policies
4. Create dashboard views with charts
5. Build reporting/export functionality
6. Add automated tests

**Questions or Need Help?**
This architecture is production-ready and follows enterprise-level Laravel standards. Adjust as needed for your specific business requirements.
