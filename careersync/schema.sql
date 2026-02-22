-- ============================================================
-- CareerSync – Smart Campus Placement Ecosystem
-- Complete MySQL Schema
-- ============================================================

CREATE DATABASE IF NOT EXISTS careersync CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE careersync;

-- ============================================================
-- USERS TABLE (all roles)
-- ============================================================
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','student','alumni') NOT NULL DEFAULT 'student',
    avatar VARCHAR(255) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB;

-- ============================================================
-- STUDENTS TABLE
-- ============================================================
CREATE TABLE students (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    roll_number VARCHAR(30) DEFAULT NULL,
    branch VARCHAR(80) NOT NULL DEFAULT '',
    cgpa DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    backlogs TINYINT UNSIGNED NOT NULL DEFAULT 0,
    year_of_passing YEAR DEFAULT NULL,
    phone VARCHAR(15) DEFAULT NULL,
    linkedin_url VARCHAR(255) DEFAULT NULL,
    github_url VARCHAR(255) DEFAULT NULL,
    resume_path VARCHAR(255) DEFAULT NULL,
    placement_status ENUM('not_placed','placed','in_process') DEFAULT 'not_placed',
    placed_company VARCHAR(150) DEFAULT NULL,
    placed_package DECIMAL(10,2) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_branch (branch),
    INDEX idx_cgpa (cgpa),
    INDEX idx_placement_status (placement_status)
) ENGINE=InnoDB;

-- ============================================================
-- STUDENT SKILLS
-- ============================================================
CREATE TABLE student_skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    proficiency ENUM('beginner','intermediate','advanced','expert') DEFAULT 'intermediate',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY uq_student_skill (student_id, skill_name),
    INDEX idx_skill_name (skill_name)
) ENGINE=InnoDB;

-- ============================================================
-- STUDENT PROJECTS
-- ============================================================
CREATE TABLE student_projects (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    tech_stack VARCHAR(255) DEFAULT NULL,
    project_url VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- ALUMNI TABLE
-- ============================================================
CREATE TABLE alumni (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    graduation_year YEAR DEFAULT NULL,
    branch VARCHAR(80) DEFAULT NULL,
    current_company VARCHAR(150) DEFAULT NULL,
    job_role VARCHAR(150) DEFAULT NULL,
    years_experience TINYINT UNSIGNED DEFAULT 0,
    linkedin_url VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(15) DEFAULT NULL,
    is_mentor TINYINT(1) DEFAULT 0,
    bio TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- PLACEMENT DRIVES
-- ============================================================
CREATE TABLE drives (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL,
    job_role VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    min_cgpa DECIMAL(4,2) NOT NULL DEFAULT 0.00,
    max_backlogs TINYINT UNSIGNED NOT NULL DEFAULT 0,
    allowed_branches TEXT NOT NULL COMMENT 'JSON array of branches',
    required_skills TEXT DEFAULT NULL COMMENT 'JSON array of skills',
    package_lpa DECIMAL(6,2) DEFAULT NULL,
    drive_date DATE NOT NULL,
    registration_deadline DATE DEFAULT NULL,
    venue VARCHAR(255) DEFAULT NULL,
    status ENUM('upcoming','active','completed','cancelled') DEFAULT 'upcoming',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id),
    INDEX idx_drive_date (drive_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- APPLICATIONS
-- ============================================================
CREATE TABLE applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    drive_id INT UNSIGNED NOT NULL,
    status ENUM('applied','aptitude','aptitude_cleared','interview_scheduled','selected','rejected','on_hold') DEFAULT 'applied',
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT DEFAULT NULL,
    UNIQUE KEY uq_student_drive (student_id, drive_id),
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (drive_id) REFERENCES drives(id) ON DELETE CASCADE,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- INTERVIEWS
-- ============================================================
CREATE TABLE interviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    student_id INT UNSIGNED NOT NULL,
    drive_id INT UNSIGNED NOT NULL,
    interview_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    venue VARCHAR(255) DEFAULT NULL,
    interview_type ENUM('technical','hr','managerial','group_discussion','aptitude') DEFAULT 'technical',
    interviewer_name VARCHAR(100) DEFAULT NULL,
    result ENUM('pending','cleared','rejected') DEFAULT 'pending',
    feedback TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (drive_id) REFERENCES drives(id) ON DELETE CASCADE,
    UNIQUE KEY uq_student_slot (student_id, interview_date, start_time),
    INDEX idx_interview_date (interview_date)
) ENGINE=InnoDB;

-- ============================================================
-- NOTIFICATIONS
-- ============================================================
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info','success','warning','danger') DEFAULT 'info',
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB;

-- ============================================================
-- JOB REFERRALS (Alumni)
-- ============================================================
CREATE TABLE referrals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT UNSIGNED NOT NULL,
    job_title VARCHAR(150) NOT NULL,
    company_name VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    required_skills TEXT DEFAULT NULL,
    experience_required VARCHAR(50) DEFAULT NULL,
    apply_link VARCHAR(255) DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
    INDEX idx_active (is_active)
) ENGINE=InnoDB;

-- ============================================================
-- MENTORSHIP SLOTS (Alumni)
-- ============================================================
CREATE TABLE mentorship_slots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    alumni_id INT UNSIGNED NOT NULL,
    slot_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME NOT NULL,
    topic VARCHAR(200) DEFAULT NULL,
    max_students TINYINT UNSIGNED DEFAULT 1,
    booked_by INT UNSIGNED DEFAULT NULL,
    status ENUM('available','booked','completed','cancelled') DEFAULT 'available',
    meeting_link VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_alumni_slot (alumni_id, slot_date, start_time),
    FOREIGN KEY (alumni_id) REFERENCES alumni(id) ON DELETE CASCADE,
    FOREIGN KEY (booked_by) REFERENCES students(id) ON DELETE SET NULL,
    INDEX idx_slot_date (slot_date),
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- ============================================================
-- SKILL LOGS (for ML-based prediction)
-- ============================================================
CREATE TABLE skill_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    skill_name VARCHAR(100) NOT NULL,
    was_placed TINYINT(1) DEFAULT 0,
    drive_id INT UNSIGNED DEFAULT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    INDEX idx_skill_placed (skill_name, was_placed)
) ENGINE=InnoDB;

-- ============================================================
-- RESUME ANALYSIS LOGS (Gemini)
-- ============================================================
CREATE TABLE resume_analysis_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_id INT UNSIGNED NOT NULL,
    score TINYINT UNSIGNED DEFAULT 0,
    ats_score TINYINT UNSIGNED DEFAULT 0,
    missing_keywords TEXT DEFAULT NULL,
    suggestions TEXT DEFAULT NULL,
    placement_probability TINYINT UNSIGNED DEFAULT 0,
    raw_response LONGTEXT DEFAULT NULL,
    analyzed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- CHATBOT LOGS
-- ============================================================
CREATE TABLE chatbot_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    message TEXT NOT NULL,
    response TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Default admin
INSERT INTO users (name, email, password, role) VALUES
('TPO Admin', 'admin@careersync.edu', '$2y$12$sfxeGgMuXgFACpqKuZI6guHdVF5rmysOuajyq1/7kQ19JDr/PF9Sa', 'admin'),
('Rahul Sharma', 'rahul@student.edu', '$2y$12$sfxeGgMuXgFACpqKuZI6guHdVF5rmysOuajyq1/7kQ19JDr/PF9Sa', 'student'),
('Priya Patel', 'priya@student.edu', '$2y$12$sfxeGgMuXgFACpqKuZI6guHdVF5rmysOuajyq1/7kQ19JDr/PF9Sa', 'student'),
('Amit Verma', 'amit@alumni.edu', '$2y$12$sfxeGgMuXgFACpqKuZI6guHdVF5rmysOuajyq1/7kQ19JDr/PF9Sa', 'alumni');
-- Default password for all: "password"

INSERT INTO students (user_id, roll_number, branch, cgpa, backlogs, year_of_passing) VALUES
(2, 'CS2021001', 'Computer Science', 8.20, 0, 2025),
(3, 'IT2021042', 'Information Technology', 7.50, 1, 2025);

INSERT INTO student_skills (student_id, skill_name, proficiency) VALUES
(1,'Python','advanced'),(1,'SQL','intermediate'),(1,'React','intermediate'),
(1,'Machine Learning','beginner'),
(2,'Java','advanced'),(2,'Spring Boot','intermediate'),(2,'MySQL','intermediate');

INSERT INTO alumni (user_id, graduation_year, branch, current_company, job_role, years_experience, is_mentor) VALUES
(4, 2021, 'Computer Science', 'Google India', 'Software Engineer', 3, 1);

INSERT INTO drives (company_name, job_role, description, min_cgpa, max_backlogs, allowed_branches, required_skills, package_lpa, drive_date, registration_deadline, venue, status, created_by) VALUES
('TCS Digital', 'Software Engineer', 'TCS Digital hiring for SDE role', 7.00, 2, '["Computer Science","Information Technology","Electronics"]', '["Python","SQL","Java"]', 7.50, DATE_ADD(CURDATE(), INTERVAL 7 DAY), DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'Seminar Hall A', 'upcoming', 1),
('Infosys Systems', 'Associate Developer', 'Infosys mass hiring 2025', 6.50, 3, '["Computer Science","Information Technology","Mechanical","Civil"]', '["Java","Python","C++"]', 4.50, DATE_ADD(CURDATE(), INTERVAL 14 DAY), DATE_ADD(CURDATE(), INTERVAL 10 DAY), 'Main Auditorium', 'upcoming', 1),
('Wipro Limited', 'Project Engineer', 'Wipro NLTH hiring', 6.00, 3, '["Computer Science","Information Technology","Electronics","Electrical"]', '["Python","Java","SQL"]', 3.50, DATE_ADD(CURDATE(), INTERVAL 21 DAY), DATE_ADD(CURDATE(), INTERVAL 17 DAY), 'Conference Room B', 'upcoming', 1);

-- Log skills for placed analysis
INSERT INTO skill_logs (student_id, skill_name, was_placed) VALUES
(1,'Python',1),(1,'SQL',1),(1,'PowerBI',0),
(2,'Python',0),(2,'SQL',0),(2,'PowerBI',0);
