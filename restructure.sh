#!/bin/bash

# Items
cp items.php items/index.php
cp item_edit.php items/edit.php
cp item_barcodes.php items/barcodes.php

# Pullsheets
cp pullsheets.php pullsheets/index.php
cp pullsheet_create.php pullsheets/create.php
cp pullsheet_edit.php pullsheets/edit.php
cp pullsheet_view.php pullsheets/view.php

# Change Orders
cp change_orders.php change-orders/index.php
cp change_order_create.php change-orders/create.php
cp change_order_edit.php change-orders/edit.php
cp change_order_view.php change-orders/view.php

# Users
cp user_management.php users/index.php
cp user_settings.php users/settings.php

# Shows (complete the migration)
cp show_tracker.php shows/tracker.php

# Repairs
cp repairs.php repairs/index.php

# Reports
cp reports.php reports/index.php

# Calendar
cp production_calendar.php calendar/index.php

# Auth
cp login.php auth/login.php
cp logout.php auth/logout.php
cp change_password.php auth/change-password.php

# Student
cp student_requests.php student/index.php

# Admin
cp admin_approvals.php admin/approvals.php
cp settings.php admin/settings.php

# Operations
cp pick_mode.php operations/pick.php
cp return_mode.php operations/return.php

# API
cp api_notifications.php api/notifications.php
cp api_quick_lookup.php api/quick-lookup.php

# Tools
cp paperwork.php tools/paperwork.php
cp csv_template.php tools/csv-template.php
cp import_csv.php tools/import-csv.php
cp run_migrations.php tools/run-migrations.php

echo "File restructure complete!"
