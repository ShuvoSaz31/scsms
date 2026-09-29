CREATE DATABASE IF NOT EXISTS student_service_management
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE student_service_management;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS feedback,
    notifications,
    attachments,
    complaint_status_history,
    complaint_assignments,
    complaints,
    complaint_subcategories,
    complaint_categories,
    students,
    staff,
    user_phones,
    user_emails,
    user_roles,
    roles,
    departments,
    users;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(30),
    profile_photo VARCHAR(255),
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE user_emails (
    email_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    INDEX (user_id)
);

CREATE TABLE user_phones (
    phone_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    phone VARCHAR(30) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    UNIQUE (user_id, phone),
    INDEX (user_id)
);

CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255)
);

CREATE TABLE user_roles (
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles (role_id) ON DELETE CASCADE
);

CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) UNIQUE NOT NULL,
    description VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE students (
    student_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    student_number VARCHAR(50) UNIQUE NOT NULL,
    department_id INT,
    program VARCHAR(100),
    trimester VARCHAR(50),
    status VARCHAR(30) DEFAULT 'ACTIVE',
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments (department_id) ON DELETE SET NULL
);

CREATE TABLE staff (
    staff_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNIQUE NOT NULL,
    staff_number VARCHAR(50) UNIQUE NOT NULL,
    department_id INT,
    designation VARCHAR(100),
    status VARCHAR(30) DEFAULT 'ACTIVE',
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments (department_id) ON DELETE SET NULL
);

CREATE TABLE complaint_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) UNIQUE NOT NULL,
    description VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1
);

CREATE TABLE complaint_subcategories (
    subcategory_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    subcategory_name VARCHAR(100) NOT NULL,
    description VARCHAR(255),
    is_active TINYINT(1) DEFAULT 1,
    UNIQUE (category_id, subcategory_name),
    FOREIGN KEY (category_id) REFERENCES complaint_categories (category_id) ON DELETE CASCADE
);

CREATE TABLE complaints (
    complaint_id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_number VARCHAR(30) UNIQUE NOT NULL,
    student_id INT NOT NULL,
    category_id INT NOT NULL,
    subcategory_id INT NULL,
    department_id INT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    priority ENUM('Low', 'Medium', 'High', 'Critical') NOT NULL DEFAULT 'Medium',
    status ENUM('SUBMITTED', 'UNDER_REVIEW', 'ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED', 'IGNORED') NOT NULL DEFAULT 'SUBMITTED',
    location VARCHAR(200),
    resolution_description TEXT NULL,
    resolved_by INT NULL,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    closed_at DATETIME NULL,
    FOREIGN KEY (student_id) REFERENCES students (student_id),
    FOREIGN KEY (category_id) REFERENCES complaint_categories (category_id),
    FOREIGN KEY (subcategory_id) REFERENCES complaint_subcategories (subcategory_id) ON DELETE SET NULL,
    FOREIGN KEY (department_id) REFERENCES departments (department_id) ON DELETE SET NULL,
    FOREIGN KEY (resolved_by) REFERENCES users (user_id) ON DELETE SET NULL,
    INDEX (student_id),
    INDEX (department_id),
    INDEX (status),
    INDEX (priority),
    INDEX (submitted_at)
);

CREATE TABLE complaint_assignments (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    staff_id INT NOT NULL,
    assigned_by INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unassigned_at DATETIME NULL,
    remarks VARCHAR(500),
    FOREIGN KEY (complaint_id) REFERENCES complaints (complaint_id) ON DELETE CASCADE,
    FOREIGN KEY (staff_id) REFERENCES staff (staff_id),
    FOREIGN KEY (assigned_by) REFERENCES users (user_id),
    INDEX (staff_id)
);

CREATE TABLE complaint_status_history (
    history_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    old_status VARCHAR(30),
    new_status VARCHAR(30) NOT NULL,
    changed_by INT NOT NULL,
    remarks VARCHAR(500),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints (complaint_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users (user_id),
    INDEX (complaint_id)
);

CREATE TABLE attachments (
    attachment_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    uploaded_by INT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(150),
    file_size BIGINT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints (complaint_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users (user_id)
);

CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    complaint_id INT NULL,
    title VARCHAR(200) NOT NULL,
    message VARCHAR(500) NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (user_id) ON DELETE CASCADE,
    FOREIGN KEY (complaint_id) REFERENCES complaints (complaint_id) ON DELETE SET NULL,
    INDEX (user_id)
);

CREATE TABLE feedback (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT UNIQUE NOT NULL,
    student_id INT NOT NULL,
    rating TINYINT NOT NULL,
    comment VARCHAR(1000),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints (complaint_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students (student_id),
    CHECK (rating BETWEEN 1 AND 5)
);
