#!/bin/bash

# Fix pullsheets/create.php - Cancel button should check show and use absolute paths
sed -i "s|href=\"<?php echo \$showId ? 'show_edit.php?id=' \. \$showId : 'shows.php'; ?>\"|href=\"<?php echo \$showId ? '/shows/edit?id=' . \$showId : '/shows/'; ?>\"|g" pullsheets/create.php

echo "Checking all files for relative paths that need fixing..."

# List files to manually check
echo "Files that may need manual review:"
grep -r "href=\"shows" pullsheets/ change-orders/ items/ --include="*.php" 2>/dev/null | cut -d: -f1 | sort | uniq
grep -r "href=\"pullsheets" shows/ change-orders/ items/ --include="*.php" 2>/dev/null | cut -d: -f1 | sort | uniq
grep -r "href=\"change_orders" pullsheets/ shows/ items/ --include="*.php" 2>/dev/null | cut -d: -f1 | sort | uniq

