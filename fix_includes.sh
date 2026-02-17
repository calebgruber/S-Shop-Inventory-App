#!/bin/bash

# Find all PHP files in subdirectories (not root) and fix include paths
find items pullsheets change-orders users shows repairs reports calendar auth student admin operations api tools -name "*.php" -type f | while read file; do
    # Update include/require statements to go up one level
    sed -i "s|require_once 'includes/|require_once '../includes/|g" "$file"
    sed -i "s|require_once \"includes/|require_once \"../includes/|g" "$file"
    sed -i "s|require 'includes/|require '../includes/|g" "$file"
    sed -i "s|require \"includes/|require \"../includes/|g" "$file"
    sed -i "s|include_once 'includes/|include_once '../includes/|g" "$file"
    sed -i "s|include_once \"includes/|include_once \"../includes/|g" "$file"
    sed -i "s|include 'includes/|include '../includes/|g" "$file"
    sed -i "s|include \"includes/|include \"../includes/|g" "$file"
done

echo "Include paths fixed!"
