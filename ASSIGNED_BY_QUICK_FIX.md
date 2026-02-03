# Quick Fix: Missing assigned_by Column

## The Problem
```
ERROR: Unknown column 'assigned_by' in 'INSERT INTO'
```

When trying to assign users to shows, the system crashed because the database table was missing a column.

---

## The Solution

Added `assigned_by` column to `user_show_assignments` table to track which admin made each assignment.

---

## What You Need to Do

### Option 1: Table Doesn't Exist Yet
```bash
mysql -u voxelnodes_sshop_dev -p voxelnodes_sshop_dev < \
  database/add_user_show_assignments_table.sql
```

### Option 2: Table Already Exists (Missing Column)
```bash
mysql -u voxelnodes_sshop_dev -p voxelnodes_sshop_dev < \
  database/add_assigned_by_column.sql
```

### Not Sure? Run Option 2
The ALTER TABLE migration is safe to run multiple times and checks if the column already exists.

---

## Verify It Worked

```sql
DESCRIBE user_show_assignments;
```

You should see `assigned_by` in the list of columns.

---

## Test It

1. Login as admin
2. Go to User Management
3. Click the calendar icon next to a user
4. Assign shows
5. Click "Update Show Assignments"
6. ✅ Should work without errors!

---

## What Changed

**Before**:
- Table had: id, user_id, show_id, created_at
- INSERT tried to use: user_id, show_id, assigned_by ❌

**After**:
- Table now has: id, user_id, show_id, assigned_by, created_at ✅
- INSERT works perfectly!

---

## Benefits

✅ **Audit Trail**: Know who assigned which users to shows
✅ **Accountability**: Track administrative actions
✅ **Error Fixed**: No more column not found errors

---

## Files Involved

1. `database/add_user_show_assignments_table.sql` - Complete table (updated)
2. `database/add_assigned_by_column.sql` - ALTER TABLE for existing tables (new)
3. `ASSIGNED_BY_COLUMN_FIX.md` - Full documentation

---

## Need Help?

See `ASSIGNED_BY_COLUMN_FIX.md` for:
- Detailed troubleshooting
- Testing checklist
- SQL verification queries
- Rollback instructions (if needed)

---

**Status**: ✅ FIXED - Run Migration to Apply
