-- Migration: Allow same student (roll_no) in multiple courses.
-- Run this if you already have the database with UNIQUE on roll_no.
-- USE attendance_db;

ALTER TABLE students DROP INDEX roll_no;
ALTER TABLE students ADD UNIQUE KEY unique_roll_class (roll_no, class);
