# Fix: Missing assigned_by Column in user_show_assignments

## Problem Report

### Error Message
```
[2026-02-02 22:04:02] [EXCEPTION] PDOException: SQLSTATE[42S22]: Column not found: 1054 
Unknown column 'assigned_by' in 'INSERT INTO' in includes/db.php:37

Stack trace:
#0 includes/db.php(37): PDO->prepare('INSERT INTO use...')
#1 user_management.php(91): Database->query('INSERT INTO use...', Array)
#2 {main}
```

### Location
- **File**: `user_management.php`
- **Line**: 91-93
- **Action**: INSERT INTO user_show_assignments

### Root Cause
The code attempts to INSERT a value for the `assigned_by` column:
```php
$db->query(
    "INSERT INTO user_show_assignments (user_id, show_id, assigned_by) VALUES (?, ?, ?)",
    [$userId, (int)$showId, $currentUserId]
);
```

However, the table schema (from the previous migration) did not include this column.

---

## Solution

### Schema Update
Added the `assigned_by` column to track which admin assigned users to shows.

**Column Details**:
- **Type**: `int(11)`
- **Nullable**: Yes (NULL if admin is deleted)
- **Default**: NULL
- **Foreign Key**: References `users.id`
- **On Delete**: SET NULL (preserve assignment history)

### Why This Column is Important
1. **Audit Trail**: Know who made each assignment
2. **Accountability**: Track administrative actions
3. **Historical Data**: Preserved even if admin account is deleted
4. **Compliance**: Meet auditing requirements

---

## Files Changed

### 1. Updated: `database/add_user_show_assignments_table.sql`
**Complete table creation with assigned_by column included**

```sql
CREATE TABLE IF NOT EXISTS `user_show_assignments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `show_id` int(11) NOT NULL,
  `assigned_by` int(11) DEFAULT NULL,              -- ADDED
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_show` (`user_id`, `show_id`),
  KEY `user_id` (`user_id`),
  KEY `show_id` (`show_id`),
  KEY `assigned_by` (`assigned_by`),                -- ADDED
  CONSTRAINT `user_show_assignments_ibfk_1` 
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_show_assignments_ibfk_2` 
    FOREIGN KEY (`show_id`) REFERENCES `shows` (`id`) ON DELETE CASCADE,
  CONSTRAINT `user_show_assignments_ibfk_3`         -- ADDED
    FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

### 2. Created: `database/add_assigned_by_column.sql`
**ALTER TABLE migration for existing installations**

This migration is for users who already created the table without the `assigned_by` column.

```sql
-- Add the assigned_by column if it doesn't exist
ALTER TABLE `user_show_assignments` 
ADD COLUMN IF NOT EXISTS `assigned_by` int(11) DEFAULT NULL AFTER `show_id`,
ADD INDEX IF NOT EXISTS `assigned_by` (`assigned_by`);

-- Conditionally add foreign key constraint
-- (Uses prepared statement to check existence first)
```

---

## Deployment Instructions

### Scenario 1: New Installation (Table Doesn't Exist)

Run the complete table creation script:

```bash
mysql -u voxelnodes_sshop_dev -p voxelnodes_sshop_dev < \
  database/add_user_show_assignments_table.sql
```

**Verification**:
```sql
DESCRIBE user_show_assignments;
```

Expected output should include:
```
+-------------+-------------+------+-----+-------------------+
| Field       | Type        | Null | Key | Default           |
+-------------+-------------+------+-----+-------------------+
| id          | int(11)     | NO   | PRI | NULL              |
| user_id     | int(11)     | NO   | MUL | NULL              |
| show_id     | int(11)     | NO   | MUL | NULL              |
| assigned_by | int(11)     | YES  | MUL | NULL              |
| created_at  | timestamp   | YES  |     | CURRENT_TIMESTAMP |
+-------------+-------------+------+-----+-------------------+
```

### Scenario 2: Existing Installation (Table Exists, Missing Column)

Run the ALTER TABLE migration:

```bash
mysql -u voxelnodes_sshop_dev -p voxelnodes_sshop_dev < \
  database/add_assigned_by_column.sql
```

**Verification**:
```sql
SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE 
FROM information_schema.COLUMNS 
WHERE TABLE_SCHEMA = 'voxelnodes_sshop_dev' 
  AND TABLE_NAME = 'user_show_assignments' 
  AND COLUMN_NAME = 'assigned_by';
```

Expected output:
```
+-------------+-------------+-------------+
| COLUMN_NAME | COLUMN_TYPE | IS_NULLABLE |
+-------------+-------------+-------------+
| assigned_by | int(11)     | YES         |
+-------------+-------------+-------------+
```

### Scenario 3: Already Fixed (Column Already Exists)

Both migrations are safe to run multiple times. They check for existence before making changes.

---

## Testing Checklist

### 1. Verify Migration Success
```sql
-- Check table structure
DESCRIBE user_show_assignments;

-- Check foreign key constraints
SELECT 
    CONSTRAINT_NAME,
    COLUMN_NAME,
    REFERENCED_TABLE_NAME,
    REFERENCED_COLUMN_NAME
FROM information_schema.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = 'voxelnodes_sshop_dev'
  AND TABLE_NAME = 'user_show_assignments'
  AND REFERENCED_TABLE_NAME IS NOT NULL;
```

Expected constraints:
- `user_show_assignments_ibfk_1`: user_id → users.id
- `user_show_assignments_ibfk_2`: show_id → shows.id
- `user_show_assignments_ibfk_3`: assigned_by → users.id

### 2. Test Show Assignment Functionality

**Steps**:
1. Login as an admin user
2. Navigate to User Management page
3. Find a designer or production audio user
4. Click the calendar icon (Show Assignments button)
5. Select one or more shows
6. Click "Update Show Assignments"
7. Verify success message appears
8. Check no errors in logs

**Expected Result**:
- ✅ No PDOException errors
- ✅ Success message: "Show assignments updated successfully"
- ✅ Assignments saved to database
- ✅ `assigned_by` field populated with current admin's ID

### 3. Verify Data Integrity

```sql
-- Check recent assignments
SELECT 
    usa.id,
    usa.user_id,
    u1.email as user_email,
    usa.show_id,
    s.name as show_name,
    usa.assigned_by,
    u2.email as assigned_by_email,
    usa.created_at
FROM user_show_assignments usa
LEFT JOIN users u1 ON usa.user_id = u1.id
LEFT JOIN shows s ON usa.show_id = s.id
LEFT JOIN users u2 ON usa.assigned_by = u2.id
ORDER BY usa.created_at DESC
LIMIT 10;
```

Expected output should show:
- User assignments
- Associated shows
- Admin who made the assignment
- Timestamp of assignment

### 4. Test Edge Cases

**Test Case 1: Assign Multiple Shows**
- Assign 3 shows to one user
- Verify all assignments saved with assigned_by

**Test Case 2: Remove All Assignments**
- Clear all shows for a user
- Verify DELETE and new INSERTs work

**Test Case 3: Reassign Shows**
- Change show assignments for a user
- Verify old assignments deleted, new ones created

**Test Case 4: Admin Deletion (Optional)**
```sql
-- Check behavior when admin is deleted
-- 1. Create assignment
-- 2. Delete the admin user
-- 3. Verify assigned_by becomes NULL (not the whole record deleted)
```

---

## Before/After Comparison

### Before (Broken)
```
Table: user_show_assignments
+------------+----------+------+-----+-------------------+
| Field      | Type     | Null | Key | Default           |
+------------+----------+------+-----+-------------------+
| id         | int(11)  | NO   | PRI | NULL              |
| user_id    | int(11)  | NO   | MUL | NULL              |
| show_id    | int(11)  | NO   | MUL | NULL              |
| created_at | timestamp| YES  |     | CURRENT_TIMESTAMP |
+------------+----------+------+-----+-------------------+

INSERT Statement:
INSERT INTO user_show_assignments (user_id, show_id, assigned_by) 
VALUES (?, ?, ?)
                                                       ^^^^^^^^^^^
                                                       ERROR: Column doesn't exist
```

### After (Fixed)
```
Table: user_show_assignments
+-------------+----------+------+-----+-------------------+
| Field       | Type     | Null | Key | Default           |
+-------------+----------+------+-----+-------------------+
| id          | int(11)  | NO   | PRI | NULL              |
| user_id     | int(11)  | NO   | MUL | NULL              |
| show_id     | int(11)  | NO   | MUL | NULL              |
| assigned_by | int(11)  | YES  | MUL | NULL              | ✅ ADDED
| created_at  | timestamp| YES  |     | CURRENT_TIMESTAMP |
+-------------+----------+------+-----+-------------------+

INSERT Statement:
INSERT INTO user_show_assignments (user_id, show_id, assigned_by) 
VALUES (?, ?, ?)
                                                       ✅ Works!
```

---

## Benefits of This Fix

### 1. Audit Trail
Every show assignment now tracks who made it:
```sql
SELECT 
    u.first_name, u.last_name, u.email,
    s.name as show_name,
    admin.email as assigned_by_admin,
    usa.created_at
FROM user_show_assignments usa
JOIN users u ON usa.user_id = u.id
JOIN shows s ON usa.show_id = s.id
LEFT JOIN users admin ON usa.assigned_by = admin.id;
```

### 2. Accountability
If issues arise, you can trace who assigned which users to shows.

### 3. Historical Data
If an admin leaves and their account is deleted:
- Assignments remain intact (CASCADE would delete them)
- `assigned_by` becomes NULL (SET NULL preserves history)
- You still know assignments existed, just not who made them

### 4. Compliance
Meet auditing requirements for access control and assignments.

---

## Troubleshooting

### Issue: Migration Fails with "Column already exists"
**Cause**: Column was manually added or migration ran twice.
**Solution**: This is expected and safe. The migration checks for existence.

### Issue: Foreign key constraint fails
**Cause**: Orphaned records or referential integrity issue.
**Solution**:
```sql
-- Check for invalid assigned_by values
SELECT DISTINCT assigned_by 
FROM user_show_assignments 
WHERE assigned_by IS NOT NULL
  AND assigned_by NOT IN (SELECT id FROM users);

-- Clean up if needed (set to NULL)
UPDATE user_show_assignments 
SET assigned_by = NULL 
WHERE assigned_by NOT IN (SELECT id FROM users);
```

### Issue: Still getting "Column not found" error
**Cause**: Migration not run or cached query plan.
**Solution**:
1. Verify column exists: `DESCRIBE user_show_assignments;`
2. Restart MySQL if needed: `sudo service mysql restart`
3. Clear PHP opcache if applicable: `opcache_reset();`

---

## Rollback (If Needed)

If you need to remove the `assigned_by` column:

```sql
-- Remove foreign key constraint first
ALTER TABLE `user_show_assignments` 
DROP FOREIGN KEY `user_show_assignments_ibfk_3`;

-- Remove index
ALTER TABLE `user_show_assignments` 
DROP INDEX `assigned_by`;

-- Remove column
ALTER TABLE `user_show_assignments` 
DROP COLUMN `assigned_by`;
```

**Warning**: This will lose the audit trail data. Only do this if absolutely necessary.

---

## Related Files

- `user_management.php` (line 91-93) - INSERT statement that uses assigned_by
- `database/add_user_show_assignments_table.sql` - Complete table creation
- `database/add_assigned_by_column.sql` - ALTER TABLE migration
- `MULTIPLE_ISSUES_FIX.md` - Previous related fixes

---

## Summary

| Aspect | Status |
|--------|--------|
| **Problem** | Column 'assigned_by' not found in INSERT |
| **Root Cause** | Table schema missing column |
| **Solution** | Added column + foreign key + index |
| **Migration** | 2 SQL files (CREATE + ALTER) |
| **Breaking Changes** | None (column is nullable) |
| **User Action** | Run appropriate migration SQL |
| **Testing** | Assign shows to users |
| **Benefits** | Audit trail, accountability, compliance |

**Status**: ✅ FIXED - Migration Required
