-- Add email column to users table for existing installations
-- Add email column (run once; ignore error if column already exists)
ALTER TABLE users ADD COLUMN email VARCHAR(255) DEFAULT NULL AFTER username;
