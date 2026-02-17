# Application Restructure Plan

## Directory Structure

### /items/
- index.php ← items.php
- edit.php ← item_edit.php
- barcodes.php ← item_barcodes.php

### /pullsheets/
- index.php ← pullsheets.php
- create.php ← pullsheet_create.php
- edit.php ← pullsheet_edit.php
- view.php ← pullsheet_view.php

### /change-orders/
- index.php ← change_orders.php
- create.php ← change_order_create.php
- edit.php ← change_order_edit.php
- view.php ← change_order_view.php

### /users/
- index.php ← user_management.php
- settings.php ← user_settings.php

### /shows/ (existing - complete)
- index.php ← shows.php
- create.php ← show_create.php
- edit.php ← show_edit.php
- tracker.php ← show_tracker.php

### /repairs/
- index.php ← repairs.php

### /reports/
- index.php ← reports.php

### /calendar/
- index.php ← production_calendar.php

### /auth/
- login.php ← login.php
- logout.php ← logout.php
- change-password.php ← change_password.php

### /student/
- index.php ← student_requests.php

### /admin/
- approvals.php ← admin_approvals.php
- settings.php ← settings.php

### /operations/
- pick.php ← pick_mode.php
- return.php ← return_mode.php

### /api/
- notifications.php ← api_notifications.php
- quick-lookup.php ← api_quick_lookup.php

### /tools/
- paperwork.php ← paperwork.php
- csv-template.php ← csv_template.php
- import-csv.php ← import_csv.php
- run-migrations.php ← run_migrations.php

### Root Level (keep)
- index.php (dashboard)
