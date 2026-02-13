-- Create database first (change name if you like)
CREATE DATABASE IF NOT EXISTS attendance_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE attendance_db;

-- Users table (admin/teacher)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Constraints: username alphanumeric+underscore, full_name letters/spaces/hyphens
    CONSTRAINT chk_username CHECK (username REGEXP '^[a-zA-Z0-9_]{3,50}$'),
    CONSTRAINT chk_fullname CHECK (full_name REGEXP '^[a-zA-Z \\-\\']{2,100}$')
);

-- Students table
-- Note: 'class' column stores the course code (e.g., 'COS 341')
-- Same student (roll_no) can appear in multiple courses; unique per (roll_no, class)
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_name VARCHAR(100) NOT NULL,
    roll_no VARCHAR(50) NOT NULL,
    class VARCHAR(50) NOT NULL DEFAULT 'COS 341',  -- Course code (renamed from class for clarity)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_roll_class (roll_no, class),
    -- Constraints for valid characters (roll_no 2-50 chars to match app)
    CONSTRAINT chk_student_name CHECK (student_name REGEXP '^[a-zA-Z \\-\\']{2,100}$'),
    CONSTRAINT chk_roll_no CHECK (roll_no REGEXP '^[a-zA-Z0-9\\-/]{2,50}$'),
    CONSTRAINT chk_course CHECK (class REGEXP '^[a-zA-Z0-9 \\-]{2,50}$')
);

-- Attendance table
-- Date restrictions: Only dates within 1 year of current date allowed (enforced in application)
CREATE TABLE IF NOT EXISTS attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    date DATE NOT NULL,
    status ENUM('Present', 'Absent') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (student_id, date),
    CONSTRAINT fk_student FOREIGN KEY (student_id) REFERENCES students (id) ON DELETE CASCADE,
    -- Date must be reasonable (between 2020 and 2030)
    CONSTRAINT chk_date CHECK (date >= '2020-01-01' AND date <= '2030-12-31')
);

-- Seed admin: username=admin, password=admin123 (will be hashed below)
INSERT INTO
    users (username, password, full_name)
VALUES (
        'admin',
        '$2y$12$1b3LKJnbyekHYfdEhLCpEuaVEdtj79t/2vIiYt.33/w.1lmy.H2Va',
        'System Administrator'
    )
ON DUPLICATE KEY UPDATE
    username = username;
-- (Hash above is for 'admin123')

-- Create index for faster course filtering
CREATE INDEX idx_students_class ON students(class);
CREATE INDEX idx_attendance_date ON attendance(date);