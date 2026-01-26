-- Add archive support to shows, pullsheets, and change_orders
-- Run this migration after the main schema.sql

-- Add archived column to shows
ALTER TABLE shows 
ADD COLUMN archived BOOLEAN DEFAULT FALSE AFTER status;

-- Add archived column to pullsheets
ALTER TABLE pullsheets 
ADD COLUMN archived BOOLEAN DEFAULT FALSE AFTER status;

-- Add archived column to change_orders
ALTER TABLE change_orders 
ADD COLUMN archived BOOLEAN DEFAULT FALSE AFTER status;

-- Add index for better query performance
CREATE INDEX idx_shows_archived ON shows(archived);
CREATE INDEX idx_pullsheets_archived ON pullsheets(archived);
CREATE INDEX idx_change_orders_archived ON change_orders(archived);
