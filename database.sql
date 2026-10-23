CREATE DATABASE rifkins_exam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rifkins_exam;

-- Departments
CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Courses
CREATE TABLE courses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    level ENUM('Certificate','Diploma') NOT NULL,
    exam_body VARCHAR(50),
    duration VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);

-- Units (subjects within a course)
CREATE TABLE units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    code VARCHAR(20) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

-- Users (all roles)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin','principal','lecturer','student') NOT NULL,
    admission_no VARCHAR(30) UNIQUE,        -- students only
    staff_no VARCHAR(30) UNIQUE,            -- lecturers/principals only
    department_id INT,                       -- lecturers assigned to dept
    course_id INT,                          -- students enrolled in course
    is_active TINYINT(1) DEFAULT 1,
    must_change_password TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (course_id) REFERENCES courses(id)
);

-- Lecturer-Unit assignments (many-to-many)
CREATE TABLE lecturer_units (
    id INT AUTO_INCREMENT PRIMARY KEY,
    lecturer_id INT NOT NULL,
    unit_id INT NOT NULL,
    assigned_by INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_assign (lecturer_id, unit_id),
    FOREIGN KEY (lecturer_id) REFERENCES users(id),
    FOREIGN KEY (unit_id) REFERENCES units(id)
);

-- Unit notes
CREATE TABLE unit_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    file_path VARCHAR(255),
    content TEXT,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id),
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Assignments
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Exams
CREATE TABLE exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    duration_minutes INT NOT NULL,
    total_marks INT NOT NULL,
    pass_mark INT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    max_attempts INT DEFAULT 1,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Questions
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    option_a VARCHAR(500) NOT NULL,
    option_b VARCHAR(500) NOT NULL,
    option_c VARCHAR(500) NOT NULL,
    option_d VARCHAR(500) NOT NULL,
    correct_answer ENUM('A','B','C','D') NOT NULL,
    marks INT DEFAULT 1,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Attempts (one per try)
CREATE TABLE attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    exam_id INT NOT NULL,
    started_at DATETIME NOT NULL,
    submitted_at DATETIME,
    score INT DEFAULT 0,
    status ENUM('in_progress','submitted','timed_out') DEFAULT 'in_progress',
    retake_authorized TINYINT(1) DEFAULT 0,
    authorized_by INT,
    authorized_at DATETIME,
    FOREIGN KEY (student_id) REFERENCES users(id),
    FOREIGN KEY (exam_id) REFERENCES exams(id),
    FOREIGN KEY (authorized_by) REFERENCES users(id)
);


CREATE TABLE IF NOT EXISTS unit_notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    file_path VARCHAR(255),
    content TEXT,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATE,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Saved answers (for review)
CREATE TABLE answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option ENUM('A','B','C','D'),
    is_correct TINYINT(1) DEFAULT 0,
    FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id)
);

-- Proctoring snapshots
CREATE TABLE snapshots (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    captured_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE
);

-- Violations
CREATE TABLE violations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    violation_type VARCHAR(50) NOT NULL,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES attempts(id) ON DELETE CASCADE
);

-- Seed departments and courses (real Rifkins data)
INSERT INTO departments (name, code) VALUES
('Accounting & Finance', 'ACC'),
('Hospitality', 'HOS'),
('Travel & Tourism', 'TRT'),
('English Language', 'ENG'),
('Hairdressing & Beauty', 'BEA'),
('Information Technology', 'ICT'),
('Shipping & Logistics', 'SHL'),
('Social Work', 'SWK');

INSERT INTO courses (department_id, name, code, level, exam_body, duration) VALUES
(1, 'Certificate in Accounting and Finance', 'ACC-CERT', 'Certificate', 'ICM (UK)', '6 months'),
(1, 'Diploma in Accounting and Finance', 'ACC-DIP', 'Diploma', 'ICM (UK)', '1 year'),
(2, 'Diploma in Hospitality Management', 'HOS-DIP', 'Diploma', 'ICM (UK)', '1 year'),
(3, 'Certificate in Travel & Tourism', 'TRT-CERT', 'Certificate', 'ICM (UK)', '6 months'),
(3, 'Diploma in Tourism & Business Studies', 'TRT-DIP', 'Diploma', 'ICM (UK)', '1 year'),
(5, 'Diploma in Hairdressing and Beauty Therapy', 'BEA-DIP', 'Diploma', NULL, '1 year'),
(6, 'Diploma in Computing & Information Systems', 'ICT-DIP', 'Diploma', NULL, '1 year'),
(7, 'Diploma in Shipping and Logistics', 'SHL-DIP', 'Diploma', 'ABMA (UK)', '1 year'),
(8, 'Diploma in Social Work and Community Development', 'SWK-DIP', 'Diploma', 'KNEC', '2 years');






ALTER TABLE attempts ADD COLUMN IF NOT EXISTS retake_authorized TINYINT(1) DEFAULT 0;
ALTER TABLE attempts ADD COLUMN IF NOT EXISTS authorized_by INT NULL;
ALTER TABLE attempts ADD COLUMN IF NOT EXISTS authorized_at DATETIME NULL;


-- Student enrollment to units (lecturer assigns)
CREATE TABLE IF NOT EXISTS unit_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    unit_id INT NOT NULL,
    enrolled_by INT NOT NULL,
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_enrollment (student_id, unit_id),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
    FOREIGN KEY (enrolled_by) REFERENCES users(id)
);

-- File uploads (replaces plain text notes where needed)
CREATE TABLE IF NOT EXISTS uploaded_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    uploader_id INT NOT NULL,
    file_type ENUM('note','assignment','exam','pastpaper') NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
    FOREIGN KEY (uploader_id) REFERENCES users(id)
);

-- Tutorial videos (principal/admin uploads links)
CREATE TABLE IF NOT EXISTS tutorials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    video_url VARCHAR(500) NOT NULL,
    unit_id INT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

INSERT INTO tutorials (title, description, video_url, unit_id, created_by) VALUES
('Accounting Basics — Double Entry', 'Introduction to bookkeeping and the double-entry system.', 'https://www.youtube.com/results?search_query=double+entry+accounting', NULL, 1),
('Hospitality Management Overview', 'Front office, housekeeping, and food & beverage basics.', 'https://www.youtube.com/results?search_query=hospitality+management+course', NULL, 1),
('IT Fundamentals', 'Free full course on computer basics and programming.', 'https://www.freecodecamp.org', NULL, 1);


CREATE TABLE IF NOT EXISTS assignment_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    assignment_file_id INT NOT NULL,
    student_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    notes TEXT,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    grade INT DEFAULT NULL,
    graded_by INT DEFAULT NULL,
    graded_at DATETIME DEFAULT NULL,
    feedback TEXT,
    UNIQUE KEY unique_submission (assignment_file_id, student_id),
    FOREIGN KEY (assignment_file_id) REFERENCES uploaded_files(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (graded_by) REFERENCES users(id)
);

DROP TABLE IF EXISTS tutorials;
CREATE TABLE tutorials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    video_url VARCHAR(500) NOT NULL,
    course_id INT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Accounting tutorials
INSERT INTO tutorials (title, description, video_url, course_id, created_by) VALUES
('Accounting Basics — Full Course', 'Double entry, ledger, trial balance, and financial statements', 'https://www.youtube.com/watch?v=yYX4bvQSqbo', 1, 1),
('Double Entry Bookkeeping Tutorial', 'Learn debits and credits step by step', 'https://www.youtube.com/watch?v=VhwZ9t2b3Zk', 1, 1),
('Financial Accounting Crash Course', 'Complete intro to financial accounting', 'https://www.youtube.com/watch?v=TzBzP4Y5lLo', 1, 1),
('Diploma Accounting — Trial Balance', 'Preparing and interpreting a trial balance', 'https://www.youtube.com/watch?v=gVh0bfqOr9E', 2, 1);

-- Hospitality
INSERT INTO tutorials (title, description, video_url, course_id, created_by) VALUES
('Hospitality Management Full Course', 'Front office, housekeeping, F&B operations', 'https://www.youtube.com/watch?v=nfrTkR6fPmY', 3, 1),
('Food and Beverage Service Training', 'Table service, menu knowledge, guest handling', 'https://www.youtube.com/watch?v=Xy2tS6gF1jU', 3, 1);

-- Travel & Tourism
INSERT INTO tutorials (title, description, video_url, course_id, created_by) VALUES
('Travel and Tourism Course', 'Tourism industry, tour operations, travel agencies', 'https://www.youtube.com/watch?v=5E5cgqBH4Yg', 4, 1),
('Tourism Management Overview', 'Tourism as a business and destination marketing', 'https://www.youtube.com/watch?v=UVn0W5PZSmI', 5, 1);

-- Beauty Therapy
INSERT INTO tutorials (title, description, video_url, course_id, created_by) VALUES
('Hairdressing and Beauty — Beginner Course', 'Hair care, styling, skin care basics', 'https://www.youtube.com/watch?v=zZqz6JgGyCs', 6, 1);

-- IT (course_id 7)
INSERT INTO tutorials (title, description, video_url, course_id, created_by) VALUES
('freeCodeCamp — Full Web Development Bootcamp', 'HTML, CSS, JavaScript from scratch', 'https://www.youtube.com/watch?v=nu_pCVPKzTk', 7, 1),
('Python for Beginners — Full Course', 'Programming fundamentals in Python', 'https://www.youtube.com/watch?v=rfscVS0vtbw', 7, 1),
('Computer Networking Basics', 'OSI model, IP addressing, routing', 'https://www.youtube.com/watch?v=qiQR5rTSshw', 7, 1),
('SQL Database Tutorial for Beginners', 'Learn databases and SQL queries', 'https://www.youtube.com/watch?v=HXV3zeQKqGY', 7, 1),
('Cybersecurity Fundamentals', 'Introduction to information security', 'https://www.youtube.com/watch?v=inWWhr5tnEA', 7, 1);

-- Shipping & Logistics (course_id 8)
INSERT INTO tutorials (title, description, video_url, course_id, created_by) VALUES
('Shipping and Logistics Course', 'Freight, warehousing, supply chain basics', 'https://www.youtube.com/watch?v=8WJqJvZ8pQo', 8, 1),
('Clearing and Forwarding Process', 'Customs documentation and procedures', 'https://www.youtube.com/watch?v=yJ2rX3pWm5A', 8, 1);

-- Social Work (course_id 9)
INSERT INTO tutorials (title, description, video_url, course_id, created_by) VALUES
('Social Work and Community Development', 'Community needs assessment and intervention', 'https://www.youtube.com/watch?v=tG_kXpS7VcM', 9, 1);

ALTER TABLE cat_scores ADD COLUMN IF NOT EXISTS final_manual INT DEFAULT NULL;



-- ============ ONLINE APPLICATIONS PIPELINE ============
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    course_interest VARCHAR(200) NOT NULL,
    preferred_campus VARCHAR(50) NOT NULL,
    kcse_grade VARCHAR(10),
    message TEXT,
    status ENUM('pending','reviewed','admitted','rejected') DEFAULT 'pending',
    admission_no VARCHAR(30) DEFAULT NULL,
    reviewed_by INT DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    student_user_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- ============ ONLINE CLASS SESSIONS ============
CREATE TABLE IF NOT EXISTS online_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    unit_id INT NOT NULL,
    lecturer_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    platform ENUM('zoom','google_meet','teams','jitsi','other') NOT NULL DEFAULT 'zoom',
    join_url VARCHAR(500) NOT NULL,
    meeting_id VARCHAR(100) DEFAULT NULL,
    passcode VARCHAR(50) DEFAULT NULL,
    scheduled_at DATETIME NOT NULL,
    duration_minutes INT DEFAULT 90,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============ SESSION ATTENDANCE ============
CREATE TABLE IF NOT EXISTS session_attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    student_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('joined','left') DEFAULT 'joined',
    UNIQUE KEY unique_attend (session_id, student_id),
    FOREIGN KEY (session_id) REFERENCES online_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============ eLIBRARY RESOURCES ============
CREATE TABLE IF NOT EXISTS library_resources (
    id INT AUTO_INCREMENT PRIMARY KEY,
    course_id INT DEFAULT NULL,
    unit_id INT DEFAULT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    resource_type ENUM('ebook','journal','past_paper','video','link','note') NOT NULL DEFAULT 'ebook',
    file_path VARCHAR(255) DEFAULT NULL,
    external_url VARCHAR(500) DEFAULT NULL,
    uploaded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE SET NULL,
    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- ============ AUDIT LOG (admin monitors every activity) ============
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    user_role VARCHAR(20) DEFAULT NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id INT DEFAULT NULL,
    details TEXT,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO library_resources (course_id, unit_id, title, description, resource_type, external_url, uploaded_by) VALUES
(1, NULL, 'ICM Accounting Study Guide', 'Official ICM UK study guide for Accounting & Finance students.', 'ebook', 'https://www.icm.ac.uk', 1),
(1, NULL, 'Double Entry Bookkeeping Explained', 'FreeCodeCamp-style tutorial covering debits and credits.', 'video', 'https://www.youtube.com/results?search_query=double+entry+bookkeeping', 1),
(3, NULL, 'Hospitality Management Handbook', 'Front office, housekeeping, and food & beverage operations.', 'ebook', 'https://www.ihm-arles.com', 1),
(6, NULL, 'FreeCodeCamp Web Development Bootcamp', 'Full HTML/CSS/JS course from scratch.', 'video', 'https://www.freecodecamp.org', 1),
(7, NULL, 'Shipping & Logistics Reference', 'ABMA UK recommended reading on clearing and forwarding.', 'link', 'https://www.abma.uk.com', 1),
(9, NULL, 'Social Work Community Practice', 'KNEC-aligned community development study material.', 'ebook', 'https://www.ifsw.org', 1);