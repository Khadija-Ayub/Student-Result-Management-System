-- ============================================================
--  Student Result Management System — Database Schema
--  Import this file via phpMyAdmin > Import
-- ============================================================

CREATE DATABASE IF NOT EXISTS result_system
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE result_system;

-- ─────────────────────────────────────────
--  USERS  (admin / teacher / student)
-- ─────────────────────────────────────────
CREATE TABLE users (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  full_name   VARCHAR(100) NOT NULL,
  email       VARCHAR(100) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  role        ENUM('admin','teacher','student') NOT NULL DEFAULT 'student',
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─────────────────────────────────────────
--  CLASSES
-- ─────────────────────────────────────────
CREATE TABLE classes (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  class_name  VARCHAR(50) NOT NULL,
  section     VARCHAR(10) NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ─────────────────────────────────────────
--  STUDENTS
-- ─────────────────────────────────────────
CREATE TABLE students (
  id          INT AUTO_INCREMENT PRIMARY KEY,
  user_id     INT NOT NULL,
  roll_number VARCHAR(20) NOT NULL UNIQUE,
  class_id    INT NOT NULL,
  created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)  REFERENCES users(id)    ON DELETE CASCADE,
  FOREIGN KEY (class_id) REFERENCES classes(id)  ON DELETE CASCADE
);

-- ─────────────────────────────────────────
--  SUBJECTS
-- ─────────────────────────────────────────
CREATE TABLE subjects (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  subject_name VARCHAR(100) NOT NULL,
  class_id     INT NOT NULL,
  total_marks  INT NOT NULL DEFAULT 100,
  FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);

-- ─────────────────────────────────────────
--  RESULTS
-- ─────────────────────────────────────────
CREATE TABLE results (
  id           INT AUTO_INCREMENT PRIMARY KEY,
  student_id   INT NOT NULL,
  subject_id   INT NOT NULL,
  marks        DECIMAL(5,2) NOT NULL DEFAULT 0,
  exam_type    ENUM('Mid','Final','Assignment','Quiz') NOT NULL DEFAULT 'Final',
  entered_by   INT NOT NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_result (student_id, subject_id, exam_type),
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  FOREIGN KEY (entered_by) REFERENCES users(id)
);

-- ─────────────────────────────────────────
--  SEED DATA
-- ─────────────────────────────────────────

-- Passwords are hashed versions of: Admin@123 / Teacher@123 / Student@123
INSERT INTO users (full_name, email, password, role) VALUES
('Admin User',      'admin@school.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Ms. Sara Khan',   'teacher@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'teacher'),
('Ahmed Ali',       'ahmed@school.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student'),
('Fatima Noor',     'fatima@school.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'student');

-- NOTE: The hashed password above decodes to: password
-- After importing, use: admin@school.com / password  to login first,
-- then change passwords from the admin panel.

INSERT INTO classes (class_name, section) VALUES
('BS Computer Science', 'A'),
('BS Computer Science', 'B');

INSERT INTO students (user_id, roll_number, class_id) VALUES
(3, 'BSCS-001', 1),
(4, 'BSCS-002', 1);

INSERT INTO subjects (subject_name, class_id, total_marks) VALUES
('Data Structures & Algorithms', 1, 100),
('Object Oriented Programming',  1, 100),
('Database Management Systems',  1, 100),
('Web Engineering',               1, 100);

INSERT INTO results (student_id, subject_id, marks, exam_type, entered_by) VALUES
(1, 1, 78,  'Final', 2),
(1, 2, 85,  'Final', 2),
(1, 3, 90,  'Final', 2),
(1, 4, 72,  'Final', 2),
(2, 1, 65,  'Final', 2),
(2, 2, 70,  'Final', 2),
(2, 3, 55,  'Final', 2),
(2, 4, 80,  'Final', 2);