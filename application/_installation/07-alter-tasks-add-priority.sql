-- Add priority column to tasks table
-- Run this in phpMyAdmin on the huge database

ALTER TABLE tasks
    ADD COLUMN priority ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium'
    AFTER status;
