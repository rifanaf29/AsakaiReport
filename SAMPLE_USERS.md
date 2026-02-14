# Sample Users & Departments

This file contains the credentials for the sample users created by the seeders.

---

## 🚀 How to Seed

Run all seeders:
```bash
php artisan db:seed
```

Or run specific seeders:
```bash
php artisan db:seed --class=DepartmentSeeder
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=UserSeeder
```

Or run in order:
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=DepartmentSeeder
php artisan db:seed --class=UserSeeder
```

---

## 🏢 Sample Departments

| Code | Name | Description |
|------|------|-------------|
| PROD | Production | Manufacturing and assembly operations |
| QA | Quality Assurance | Quality control and testing |
| LOG | Logistics | Warehouse and shipping operations |
| HR | Human Resources | Employee management and development |
| FIN | Finance | Financial planning and accounting |
| IT | Information Technology | System administration and support |
| ENG | Engineering | Product design and development |
| MAINT | Maintenance | Equipment and facility maintenance |

---

## 👥 Sample Users

### Admin Accounts (Full System Access)

Can access **all departments** and have **all permissions**.

| Name | Email | Password | Department | Access Level |
|------|-------|----------|------------|--------------|
| System Administrator | admin@asakai.com | Admin@123 | IT | All Departments |
| Super Administrator | superadmin@asakai.com | Super@123 | IT | All Departments |

**Capabilities:**
- ✅ Access all departments
- ✅ Create/edit/delete departments
- ✅ Manage all users and assign roles
- ✅ Lock/unlock KPI entries
- ✅ Manage system settings
- ✅ View audit logs

---

### Manager Accounts (Department Management)

Can manage their **assigned department only**.

| Name | Email | Password | Department | Access Level |
|------|-------|----------|------------|--------------|
| Production Manager | manager.prod@asakai.com | Manager@123 | Production | Own Department Only |
| QA Manager | manager.qa@asakai.com | Manager@123 | Quality Assurance | Own Department Only |
| Logistics Manager | manager.log@asakai.com | Manager@123 | Logistics | Own Department Only |

**Capabilities:**
- ✅ Full KPI access (create, edit, delete, lock) within department
- ✅ Full CAPA access (create, edit, delete, assign, close) within department
- ✅ Create/edit users within department
- ✅ View and export reports for own department
- ❌ Cannot access other departments
- ❌ Cannot delete users
- ❌ Cannot manage system settings

---

### Regular User Accounts (Basic Access)

Can view and create entries in their **assigned department only**.

| Name | Email | Password | Department | Access Level |
|------|-------|----------|------------|--------------|
| John Doe | john.doe@asakai.com | User@123 | Production | Own Department Only |
| Jane Smith | jane.smith@asakai.com | User@123 | Production | Own Department Only |
| Mike Johnson | mike.johnson@asakai.com | User@123 | Quality Assurance | Own Department Only |
| Sarah Williams | sarah.williams@asakai.com | User@123 | Quality Assurance | Own Department Only |
| David Brown | david.brown@asakai.com | User@123 | Logistics | Own Department Only |
| Emily Davis | emily.davis@asakai.com | User@123 | Logistics | Own Department Only |

**Capabilities:**
- ✅ View KPI entries in own department
- ✅ Create KPI entries
- ✅ Edit own KPI entries
- ✅ View CAPA in own department
- ✅ Create CAPA entries
- ✅ Edit own CAPA entries
- ✅ View reports in own department
- ❌ Cannot delete KPI/CAPA
- ❌ Cannot lock KPI entries
- ❌ Cannot close CAPA action plans
- ❌ Cannot access other departments
- ❌ Cannot export reports

---

## 🧪 Testing Scenarios

### Test 1: Admin Cross-Department Access

```bash
# Login as admin
Email: admin@asakai.com
Password: Admin@123

# Expected: Can view and edit KPI/CAPA from ALL departments
```

### Test 2: Manager Department Restriction

```bash
# Login as Production Manager
Email: manager.prod@asakai.com
Password: Manager@123

# Expected: 
# ✅ Can access Production department data
# ❌ Cannot access QA or Logistics department data
```

### Test 3: User Basic Access

```bash
# Login as regular user
Email: john.doe@asakai.com
Password: User@123

# Expected:
# ✅ Can view/create/edit KPI in Production department
# ❌ Cannot delete KPI entries
# ❌ Cannot lock KPI entries
# ❌ Cannot access other departments
```

### Test 4: CAPA Business Rule

```bash
# Login as any user
# Create KPI entry with status = "NG"

# Expected:
# ✅ System should prompt to create CAPA
# ✅ CAPA should be linked to KPI entry
# ✅ is_mandatory should be true
```

---

## 📊 User Distribution by Department

| Department | Managers | Users | Total |
|------------|----------|-------|-------|
| Production | 1 | 2 | 3 |
| Quality Assurance | 1 | 2 | 3 |
| Logistics | 1 | 2 | 3 |
| IT | 2 (Admins) | 0 | 2 |
| **Total** | **5** | **6** | **11** |

---

## 🔑 Role Summary

| Role | Count | Access Level |
|------|-------|--------------|
| Admin | 2 | Full system access |
| Manager | 3 | Department management |
| User | 6 | Basic access |
| **Total** | **11** | |

---

## 💡 Tips

### Change User Password

```php
php artisan tinker

$user = User::where('email', 'john.doe@asakai.com')->first();
$user->password = bcrypt('NewPassword@123');
$user->save();
```

### Give User Access to All Departments

```php
php artisan tinker

$user = User::where('email', 'john.doe@asakai.com')->first();
$user->can_access_all_departments = true;
$user->save();
```

### Change User Department

```php
php artisan tinker

$user = User::where('email', 'john.doe@asakai.com')->first();
$newDept = Department::where('code', 'QA')->first();
$user->department_id = $newDept->id;
$user->save();
```

### Promote User to Manager

```php
php artisan tinker

$user = User::where('email', 'john.doe@asakai.com')->first();
$user->syncRoles(['manager']); // Replace current role with manager
```

### Create Custom User

```php
php artisan tinker

$dept = Department::where('code', 'PROD')->first();
$user = User::create([
    'name' => 'Custom User',
    'email' => 'custom@asakai.com',
    'password' => bcrypt('Custom@123'),
    'department_id' => $dept->id,
    'can_access_all_departments' => false,
    'email_verified_at' => now(),
]);
$user->assignRole('user');
```

---

## 🗑️ Reset Users

To remove all test users and reseed:

```bash
# WARNING: This will delete all data
php artisan migrate:fresh --seed
```

Or just reseed users:

```bash
# Delete all users first
php artisan tinker
User::query()->delete();

# Then reseed
php artisan db:seed --class=UserSeeder
```

---

## 📖 Related Documentation

- **RBAC Documentation:** [RBAC_DOCUMENTATION.md](RBAC_DOCUMENTATION.md)
- **Setup Guide:** [RBAC_SETUP_GUIDE.md](RBAC_SETUP_GUIDE.md)
- **Quick Reference:** [DEVELOPER_QUICK_REFERENCE.md](DEVELOPER_QUICK_REFERENCE.md)

---

## ⚠️ Security Notes

### For Production

**DO NOT use these default passwords in production!**

When deploying to production:
1. Change all default passwords
2. Enforce strong password policies
3. Enable two-factor authentication
4. Regularly audit user access
5. Remove test users
6. Use environment-specific credentials

### Recommended Password Policy

- Minimum 8 characters
- At least 1 uppercase letter
- At least 1 lowercase letter
- At least 1 number
- At least 1 special character
- Not a common password
- Different from previous passwords

---

## 📝 Notes

- All sample users have **verified email addresses** (`email_verified_at` is set)
- Passwords follow the pattern: `Role@123` (e.g., `Admin@123`, `Manager@123`, `User@123`)
- Admin users are assigned to IT department but can access all departments
- Users are distributed across Production, QA, and Logistics departments
- The `firstOrCreate` method prevents duplicate users when re-running the seeder

---

**Last Updated:** 2026-02-14
