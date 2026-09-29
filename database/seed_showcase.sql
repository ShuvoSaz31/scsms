USE student_service_management;
SET NAMES utf8mb4;

-- Keep the application's fixed RBAC and department/category catalog realistic.
INSERT IGNORE INTO roles (role_name, description) VALUES
    ('Student', 'Student'),
    ('Staff', 'Staff member'),
    ('Department Head', 'Department Head'),
    ('Admin', 'Administrator');

INSERT IGNORE INTO departments (department_name, description) VALUES
    ('CSE', 'Computer Science and Engineering'),
    ('EEE', 'Electrical and Electronic Engineering'),
    ('IT', 'Information Technology'),
    ('Facilities', 'Facilities and campus services'),
    ('Administration', 'Administration and student services');

INSERT IGNORE INTO complaint_categories (category_name, description) VALUES
    ('Academic', 'Teaching, courses, grades, and academic support'),
    ('IT & Internet', 'Network, account, and computing services'),
    ('Facilities', 'Buildings, utilities, and campus facilities'),
    ('Laboratory', 'Laboratory equipment and access'),
    ('Transport', 'Campus and student transport'),
    ('Library', 'Library access and resources'),
    ('Security', 'Campus safety and security'),
    ('Administration', 'Administrative and student-record services');

DROP TEMPORARY TABLE IF EXISTS showcase_digits;
DROP TEMPORARY TABLE IF EXISTS showcase_hundreds;
DROP TEMPORARY TABLE IF EXISTS showcase_numbers;
DROP TEMPORARY TABLE IF EXISTS showcase_cases;
DROP TEMPORARY TABLE IF EXISTS showcase_events;

CREATE TEMPORARY TABLE showcase_digits (n INT PRIMARY KEY);
INSERT INTO showcase_digits (n) VALUES (0),(1),(2),(3),(4),(5),(6),(7),(8),(9);
CREATE TEMPORARY TABLE showcase_hundreds (n INT PRIMARY KEY);
INSERT INTO showcase_hundreds (n) VALUES (0),(1);
CREATE TEMPORARY TABLE showcase_numbers (n INT PRIMARY KEY);
INSERT INTO showcase_numbers (n)
SELECT h.n * 100 + t.n * 10 + o.n + 1
FROM showcase_hundreds h
CROSS JOIN showcase_digits t
CROSS JOIN showcase_digits o
WHERE h.n * 100 + t.n * 10 + o.n + 1 <= 200;

-- Eight meaningful subcategories per supported category (64 total).
INSERT IGNORE INTO complaint_subcategories (category_id, subcategory_name, description)
SELECT c.category_id,
       CONCAT('Showcase issue ', LPAD(n.n, 2, '0')),
       CONCAT('Sample subcategory ', n.n, ' for ', c.category_name)
FROM complaint_categories c
CROSS JOIN showcase_numbers n
WHERE n.n <= 8;

-- Fifty students, fifty staff members, and five department heads.
INSERT IGNORE INTO users (email, password_hash, full_name, phone, is_active)
SELECT CONCAT('showcase.student', LPAD(n.n, 3, '0'), '@example.test'),
       '$2y$12$k/9CclitssQeGBya0DCQfOHBksyw8afxRs.b0qqZppBYdVddT3RFC',
       CONCAT(
           CASE MOD(n.n - 1, 10)
               WHEN 0 THEN 'Amina' WHEN 1 THEN 'Rafi' WHEN 2 THEN 'Nadia' WHEN 3 THEN 'Imran' WHEN 4 THEN 'Farah'
               WHEN 5 THEN 'Sami' WHEN 6 THEN 'Maya' WHEN 7 THEN 'Tariq' WHEN 8 THEN 'Lina' ELSE 'Arman'
           END,
           ' ',
           CASE FLOOR((n.n - 1) / 10)
               WHEN 0 THEN 'Rahman' WHEN 1 THEN 'Chowdhury' WHEN 2 THEN 'Ahmed' WHEN 3 THEN 'Karim' ELSE 'Hossain'
           END
       ),
       CONCAT('0189', LPAD(n.n, 7, '0')),
       IF(MOD(n.n, 17) = 0, 0, 1)
FROM showcase_numbers n
WHERE n.n <= 50;

INSERT IGNORE INTO users (email, password_hash, full_name, phone, is_active)
SELECT CONCAT('showcase.staff', LPAD(n.n, 3, '0'), '@example.test'),
       '$2y$12$k/9CclitssQeGBya0DCQfOHBksyw8afxRs.b0qqZppBYdVddT3RFC',
       CONCAT('Showcase Staff ', LPAD(n.n, 3, '0')),
       CONCAT('0188', LPAD(n.n, 7, '0')),
       IF(MOD(n.n, 19) = 0, 0, 1)
FROM showcase_numbers n
WHERE n.n <= 50;

INSERT IGNORE INTO users (email, password_hash, full_name, phone, is_active)
SELECT CONCAT('showcase.head', LPAD(n.n, 2, '0'), '@example.test'),
       '$2y$12$k/9CclitssQeGBya0DCQfOHBksyw8afxRs.b0qqZppBYdVddT3RFC',
       CONCAT('Showcase Department Head ', LPAD(n.n, 2, '0')),
       CONCAT('0187', LPAD(n.n, 7, '0')),
       1
FROM showcase_numbers n
WHERE n.n <= 5;

-- Every showcase user has a primary contact; most also have a secondary one.
INSERT IGNORE INTO user_emails (user_id, email, is_primary)
SELECT user_id, email, 1 FROM users WHERE email LIKE 'showcase.%@example.test';
INSERT IGNORE INTO user_emails (user_id, email, is_primary)
SELECT user_id, CONCAT('alternate.', email), 0
FROM users
WHERE email LIKE 'showcase.%@example.test';
INSERT IGNORE INTO user_phones (user_id, phone, is_primary)
SELECT user_id, phone, 1 FROM users WHERE email LIKE 'showcase.%@example.test';
INSERT IGNORE INTO user_phones (user_id, phone, is_primary)
SELECT user_id, CONCAT('0199', RIGHT(phone, 7)), 0
FROM users
WHERE email LIKE 'showcase.%@example.test';

UPDATE users u
JOIN showcase_numbers n ON n.n <= 50
SET u.profile_photo = CONCAT('uploads/avatars/showcase-avatar-', LPAD(n.n, 3, '0'), '.png')
WHERE u.email = CONCAT('showcase.student', LPAD(n.n, 3, '0'), '@example.test');

UPDATE users u
JOIN showcase_numbers n ON n.n <= 50
SET u.profile_photo = CONCAT('uploads/avatars/showcase-avatar-', LPAD(n.n + 50, 3, '0'), '.png')
WHERE u.email = CONCAT('showcase.staff', LPAD(n.n, 3, '0'), '@example.test');

UPDATE users u
JOIN showcase_numbers n ON n.n <= 5
SET u.profile_photo = CONCAT('uploads/avatars/showcase-avatar-', LPAD(n.n + 100, 3, '0'), '.png')
WHERE u.email = CONCAT('showcase.head', LPAD(n.n, 2, '0'), '@example.test');

INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.user_id, r.role_id
FROM users u
JOIN roles r ON r.role_name = CASE
    WHEN u.email LIKE 'showcase.student%' THEN 'Student'
    WHEN u.email LIKE 'showcase.staff%' THEN 'Staff'
    WHEN u.email LIKE 'showcase.head%' THEN 'Department Head'
END
WHERE u.email LIKE 'showcase.%@example.test';

INSERT IGNORE INTO students (user_id, student_number, department_id, program, trimester, status)
SELECT u.user_id,
       CONCAT('SHOW-STU-', LPAD(n.n, 4, '0')),
       d.department_id,
       CASE MOD(n.n - 1, 5)
           WHEN 0 THEN 'BSc in Computer Science' WHEN 1 THEN 'BSc in Electrical Engineering'
           WHEN 2 THEN 'BBA' WHEN 3 THEN 'BSc in Information Technology' ELSE 'BA in English'
       END,
       CONCAT('Trimester ', MOD(n.n - 1, 12) + 1),
       IF(MOD(n.n, 23) = 0, 'INACTIVE', 'ACTIVE')
FROM showcase_numbers n
JOIN users u ON u.email = CONCAT('showcase.student', LPAD(n.n, 3, '0'), '@example.test')
JOIN departments d ON d.department_name = CASE MOD(n.n - 1, 5)
    WHEN 0 THEN 'CSE' WHEN 1 THEN 'EEE' WHEN 2 THEN 'IT' WHEN 3 THEN 'Facilities' ELSE 'Administration'
END
WHERE n.n <= 50;

INSERT IGNORE INTO staff (user_id, staff_number, department_id, designation, status)
SELECT u.user_id,
       CONCAT('SHOW-STF-', LPAD(n.n, 4, '0')),
       d.department_id,
       CASE MOD(n.n - 1, 5)
           WHEN 0 THEN 'Academic Support Officer' WHEN 1 THEN 'Laboratory Technician'
           WHEN 2 THEN 'IT Support Officer' WHEN 3 THEN 'Facilities Coordinator' ELSE 'Student Services Officer'
       END,
       IF(MOD(n.n, 13) = 0, 'INACTIVE', 'ACTIVE')
FROM showcase_numbers n
JOIN users u ON u.email = CONCAT('showcase.staff', LPAD(n.n, 3, '0'), '@example.test')
JOIN departments d ON d.department_name = CASE MOD(n.n - 1, 5)
    WHEN 0 THEN 'CSE' WHEN 1 THEN 'EEE' WHEN 2 THEN 'IT' WHEN 3 THEN 'Facilities' ELSE 'Administration'
END
WHERE n.n <= 50;

INSERT IGNORE INTO staff (user_id, staff_number, department_id, designation, status)
SELECT u.user_id,
       CONCAT('SHOW-DH-', LPAD(n.n, 3, '0')),
       d.department_id,
       'Department Head',
       'ACTIVE'
FROM showcase_numbers n
JOIN users u ON u.email = CONCAT('showcase.head', LPAD(n.n, 2, '0'), '@example.test')
JOIN departments d ON d.department_name = CASE n.n
    WHEN 1 THEN 'CSE' WHEN 2 THEN 'EEE' WHEN 3 THEN 'IT' WHEN 4 THEN 'Facilities' ELSE 'Administration'
END
WHERE n.n <= 5;

-- Build 200 varied cases across all supported statuses, priorities, categories, and departments.
CREATE TEMPORARY TABLE showcase_cases (
    n INT PRIMARY KEY,
    status VARCHAR(30) NOT NULL,
    priority VARCHAR(20) NOT NULL,
    category_name VARCHAR(100) NOT NULL,
    department_name VARCHAR(100) NOT NULL,
    submitted_at DATETIME NOT NULL
);
INSERT INTO showcase_cases (n, status, priority, category_name, department_name, submitted_at)
SELECT n.n,
       CASE MOD(n.n - 1, 10)
           WHEN 0 THEN 'SUBMITTED' WHEN 1 THEN 'UNDER_REVIEW' WHEN 2 THEN 'ASSIGNED'
           WHEN 3 THEN 'IN_PROGRESS' WHEN 4 THEN 'RESOLVED' WHEN 5 THEN 'CLOSED'
           WHEN 6 THEN 'IGNORED' WHEN 7 THEN 'IN_PROGRESS' WHEN 8 THEN 'RESOLVED' ELSE 'SUBMITTED'
       END,
       CASE MOD(n.n - 1, 4) WHEN 0 THEN 'Low' WHEN 1 THEN 'Medium' WHEN 2 THEN 'High' ELSE 'Critical' END,
       CASE MOD(n.n - 1, 8)
           WHEN 0 THEN 'Academic' WHEN 1 THEN 'IT & Internet' WHEN 2 THEN 'Facilities' WHEN 3 THEN 'Laboratory'
           WHEN 4 THEN 'Transport' WHEN 5 THEN 'Library' WHEN 6 THEN 'Security' ELSE 'Administration'
       END,
       CASE MOD(n.n - 1, 8)
           WHEN 0 THEN 'CSE' WHEN 1 THEN 'IT' WHEN 2 THEN 'Facilities' WHEN 3 THEN 'EEE'
           WHEN 4 THEN 'Administration' WHEN 5 THEN 'Administration' WHEN 6 THEN 'Administration' ELSE 'Administration'
       END,
       DATE_SUB(NOW(), INTERVAL MOD(n.n * 7, 180) DAY)
FROM showcase_numbers n;

INSERT IGNORE INTO complaints (
    ticket_number, student_id, category_id, subcategory_id, department_id,
    title, description, priority, status, location, resolution_description,
    resolved_by, submitted_at, resolved_at, closed_at
)
SELECT CONCAT('SHOW-2026-', LPAD(cases.n, 6, '0')),
       student.student_id,
       category.category_id,
       CASE WHEN MOD(cases.n, 4) = 0 THEN NULL ELSE subcategory.subcategory_id END,
       department.department_id,
       CASE MOD(cases.n - 1, 12)
           WHEN 0 THEN 'Classroom projector needs repair' WHEN 1 THEN 'Unable to access campus Wi-Fi'
           WHEN 2 THEN 'Request for course advising' WHEN 3 THEN 'Laboratory workstation is unavailable'
           WHEN 4 THEN 'Water leak reported in campus building' WHEN 5 THEN 'Shuttle timetable clarification'
           WHEN 6 THEN 'Library account access issue' WHEN 7 THEN 'Request for student document'
           WHEN 8 THEN 'Campus lighting needs inspection' WHEN 9 THEN 'Network connection drops intermittently'
           WHEN 10 THEN 'Classroom seating needs attention' ELSE 'Help with student service request'
       END,
       CONCAT('Showcase sample complaint ', LPAD(cases.n, 3, '0'), '. The reported issue requires review by the appropriate service team.'),
       cases.priority,
       cases.status,
       CASE MOD(cases.n - 1, 8)
           WHEN 0 THEN 'Main campus, Building A' WHEN 1 THEN 'Library, second floor'
           WHEN 2 THEN 'North residence hall' WHEN 3 THEN 'Engineering block, Lab 2'
           WHEN 4 THEN 'East campus shuttle stop' WHEN 5 THEN 'Student center'
           WHEN 6 THEN 'Administration building' ELSE 'Main campus, Building C'
       END,
       CASE WHEN cases.status IN ('RESOLVED', 'CLOSED') THEN
           CASE MOD(cases.n, 4)
               WHEN 0 THEN 'Repaired and tested by the assigned service team.'
               WHEN 1 THEN 'Access restored after account and network checks.'
               WHEN 2 THEN 'Request completed and confirmed with the student.'
               ELSE 'Service request completed; follow-up instructions provided.'
           END
           ELSE NULL
       END,
       CASE WHEN cases.status IN ('RESOLVED', 'CLOSED') THEN resolver.user_id ELSE NULL END,
       cases.submitted_at,
       CASE WHEN cases.status IN ('RESOLVED', 'CLOSED') THEN DATE_ADD(cases.submitted_at, INTERVAL (1 + MOD(cases.n, 6)) DAY) ELSE NULL END,
       CASE WHEN cases.status = 'CLOSED' THEN DATE_ADD(cases.submitted_at, INTERVAL (2 + MOD(cases.n, 6)) DAY) ELSE NULL END
FROM showcase_cases cases
JOIN students student ON student.student_number = CONCAT('SHOW-STU-', LPAD(MOD(cases.n - 1, 50) + 1, 4, '0'))
JOIN complaint_categories category ON category.category_name = cases.category_name
JOIN departments department ON department.department_name = cases.department_name
LEFT JOIN complaint_subcategories subcategory
    ON subcategory.category_id = category.category_id
    AND subcategory.subcategory_name = CONCAT('Showcase issue ', LPAD(MOD(cases.n - 1, 8) + 1, 2, '0'))
LEFT JOIN staff resolver_profile
    ON resolver_profile.staff_number = CONCAT(
        'SHOW-STF-',
        LPAD((CASE cases.department_name WHEN 'CSE' THEN 0 WHEN 'EEE' THEN 10 WHEN 'IT' THEN 20 WHEN 'Facilities' THEN 30 ELSE 40 END) + MOD(cases.n - 1, 10) + 1, 4, '0')
    )
LEFT JOIN users resolver ON resolver.user_id = resolver_profile.user_id;

-- One current assignment for assigned/in-progress cases and closed assignment history for completed cases.
INSERT INTO complaint_assignments (complaint_id, staff_id, assigned_by, assigned_at, unassigned_at, remarks)
SELECT complaint.complaint_id,
       assignee.staff_id,
       head.user_id,
       DATE_ADD(cases.submitted_at, INTERVAL 1 DAY),
       CASE WHEN cases.status IN ('RESOLVED', 'CLOSED') THEN complaint.resolved_at ELSE NULL END,
       'Showcase seed assignment'
FROM showcase_cases cases
JOIN complaints complaint ON complaint.ticket_number = CONCAT('SHOW-2026-', LPAD(cases.n, 6, '0'))
JOIN staff assignee ON assignee.staff_number = CONCAT(
    'SHOW-STF-',
    LPAD((CASE cases.department_name WHEN 'CSE' THEN 0 WHEN 'EEE' THEN 10 WHEN 'IT' THEN 20 WHEN 'Facilities' THEN 30 ELSE 40 END) + MOD(cases.n - 1, 10) + 1, 4, '0')
)
JOIN staff head_profile ON head_profile.staff_number = CONCAT(
    'SHOW-DH-',
    LPAD(CASE cases.department_name WHEN 'CSE' THEN 1 WHEN 'EEE' THEN 2 WHEN 'IT' THEN 3 WHEN 'Facilities' THEN 4 ELSE 5 END, 3, '0')
)
JOIN users head ON head.user_id = head_profile.user_id
WHERE cases.status IN ('ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED')
  AND NOT EXISTS (
      SELECT 1 FROM complaint_assignments existing
      WHERE existing.complaint_id = complaint.complaint_id
        AND existing.remarks = 'Showcase seed assignment'
  );

-- Create a proper status timeline for each generated complaint.
CREATE TEMPORARY TABLE showcase_events (
    n INT NOT NULL,
    old_status VARCHAR(30) NULL,
    new_status VARCHAR(30) NOT NULL,
    actor_kind VARCHAR(12) NOT NULL,
    event_key VARCHAR(24) NOT NULL,
    PRIMARY KEY (n, event_key)
);
INSERT INTO showcase_events (n, old_status, new_status, actor_kind, event_key)
SELECT n, NULL, 'SUBMITTED', 'student', 'submitted' FROM showcase_cases
UNION ALL
SELECT n, 'SUBMITTED', 'UNDER_REVIEW', 'head', 'reviewed' FROM showcase_cases WHERE status = 'UNDER_REVIEW'
UNION ALL
SELECT n, 'SUBMITTED', 'ASSIGNED', 'head', 'assigned' FROM showcase_cases WHERE status IN ('ASSIGNED', 'IN_PROGRESS', 'RESOLVED', 'CLOSED')
UNION ALL
SELECT n, 'ASSIGNED', 'IN_PROGRESS', 'staff', 'in_progress' FROM showcase_cases WHERE status IN ('IN_PROGRESS', 'RESOLVED', 'CLOSED')
UNION ALL
SELECT n, 'IN_PROGRESS', 'RESOLVED', 'staff', 'resolved' FROM showcase_cases WHERE status IN ('RESOLVED', 'CLOSED')
UNION ALL
SELECT n, 'RESOLVED', 'CLOSED', 'head', 'closed' FROM showcase_cases WHERE status = 'CLOSED'
UNION ALL
SELECT n, 'SUBMITTED', 'IGNORED', 'head', 'ignored' FROM showcase_cases WHERE status = 'IGNORED';

INSERT INTO complaint_status_history (complaint_id, old_status, new_status, changed_by, remarks, changed_at)
SELECT complaint.complaint_id,
       event.old_status,
       event.new_status,
       CASE event.actor_kind
           WHEN 'student' THEN student_user.user_id
           WHEN 'staff' THEN resolver.user_id
           ELSE head.user_id
       END,
       CONCAT('Showcase seed: ', event.event_key, ' event'),
       CASE event.event_key
           WHEN 'submitted' THEN cases.submitted_at
           WHEN 'reviewed' THEN DATE_ADD(cases.submitted_at, INTERVAL 1 DAY)
           WHEN 'assigned' THEN DATE_ADD(cases.submitted_at, INTERVAL 1 DAY)
           WHEN 'in_progress' THEN DATE_ADD(cases.submitted_at, INTERVAL 2 DAY)
           WHEN 'resolved' THEN complaint.resolved_at
           WHEN 'closed' THEN complaint.closed_at
           ELSE DATE_ADD(cases.submitted_at, INTERVAL 1 DAY)
       END
FROM showcase_events event
JOIN showcase_cases cases ON cases.n = event.n
JOIN complaints complaint ON complaint.ticket_number = CONCAT('SHOW-2026-', LPAD(cases.n, 6, '0'))
JOIN students student_profile ON student_profile.student_id = complaint.student_id
JOIN users student_user ON student_user.user_id = student_profile.user_id
LEFT JOIN staff resolver_profile ON resolver_profile.staff_number = CONCAT(
    'SHOW-STF-',
    LPAD((CASE cases.department_name WHEN 'CSE' THEN 0 WHEN 'EEE' THEN 10 WHEN 'IT' THEN 20 WHEN 'Facilities' THEN 30 ELSE 40 END) + MOD(cases.n - 1, 10) + 1, 4, '0')
)
LEFT JOIN users resolver ON resolver.user_id = resolver_profile.user_id
LEFT JOIN staff head_profile ON head_profile.staff_number = CONCAT(
    'SHOW-DH-',
    LPAD(CASE cases.department_name WHEN 'CSE' THEN 1 WHEN 'EEE' THEN 2 WHEN 'IT' THEN 3 WHEN 'Facilities' THEN 4 ELSE 5 END, 3, '0')
)
LEFT JOIN users head ON head.user_id = head_profile.user_id
WHERE NOT EXISTS (
    SELECT 1 FROM complaint_status_history existing
    WHERE existing.complaint_id = complaint.complaint_id
      AND existing.remarks = CONCAT('Showcase seed: ', event.event_key, ' event')
);

-- Student notices for every complaint and staff notices for current assignments.
INSERT INTO notifications (user_id, complaint_id, title, message, is_read, created_at)
SELECT student_profile.user_id,
       complaint.complaint_id,
       CONCAT('Showcase status: ', REPLACE(LOWER(complaint.status), '_', ' ')),
       CONCAT('Showcase complaint ', complaint.ticket_number, ' is currently ', REPLACE(LOWER(complaint.status), '_', ' '), '.'),
       MOD(cases.n, 2),
       complaint.submitted_at
FROM showcase_cases cases
JOIN complaints complaint ON complaint.ticket_number = CONCAT('SHOW-2026-', LPAD(cases.n, 6, '0'))
JOIN students student_profile ON student_profile.student_id = complaint.student_id
WHERE NOT EXISTS (
    SELECT 1 FROM notifications existing
    WHERE existing.complaint_id = complaint.complaint_id
      AND existing.user_id = student_profile.user_id
      AND existing.title = CONCAT('Showcase status: ', REPLACE(LOWER(complaint.status), '_', ' '))
);

INSERT INTO notifications (user_id, complaint_id, title, message, is_read, created_at)
SELECT assignee.user_id,
       complaint.complaint_id,
       'Showcase assignment',
       CONCAT('Showcase complaint ', complaint.ticket_number, ' is in your assigned workload.'),
       MOD(cases.n, 2),
       DATE_ADD(complaint.submitted_at, INTERVAL 1 DAY)
FROM showcase_cases cases
JOIN complaints complaint ON complaint.ticket_number = CONCAT('SHOW-2026-', LPAD(cases.n, 6, '0'))
JOIN complaint_assignments assignment ON assignment.complaint_id = complaint.complaint_id AND assignment.remarks = 'Showcase seed assignment'
JOIN staff staff_profile ON staff_profile.staff_id = assignment.staff_id
JOIN users assignee ON assignee.user_id = staff_profile.user_id
WHERE cases.status IN ('ASSIGNED', 'IN_PROGRESS')
  AND NOT EXISTS (
      SELECT 1 FROM notifications existing
      WHERE existing.complaint_id = complaint.complaint_id
        AND existing.user_id = assignee.user_id
        AND existing.title = 'Showcase assignment'
  );

-- One feedback row for every resolved or closed showcase complaint (60 varied ratings).
INSERT IGNORE INTO feedback (complaint_id, student_id, rating, comment, created_at)
SELECT complaint.complaint_id,
       complaint.student_id,
       MOD(cases.n - 1, 5) + 1,
       CASE MOD(cases.n, 6)
           WHEN 0 THEN 'The issue was resolved quickly and clearly.'
           WHEN 1 THEN 'Helpful staff and a smooth follow-up.'
           WHEN 2 THEN 'The resolution worked; communication could improve.'
           WHEN 3 THEN 'Good service and clear updates.'
           WHEN 4 THEN 'The team handled this professionally.'
           ELSE 'Thank you for resolving this request.'
       END,
       DATE_ADD(complaint.resolved_at, INTERVAL 1 DAY)
FROM showcase_cases cases
JOIN complaints complaint ON complaint.ticket_number = CONCAT('SHOW-2026-', LPAD(cases.n, 6, '0'))
WHERE cases.status IN ('RESOLVED', 'CLOSED');

-- Fifty real downloadable attachment records reuse five generated synthetic PNG fixtures.
INSERT INTO attachments (complaint_id, uploaded_by, original_name, stored_name, file_path, mime_type, file_size, uploaded_at)
SELECT complaint.complaint_id,
       student_profile.user_id,
       CONCAT('Showcase evidence ', LPAD(cases.n, 3, '0'), '.png'),
       CONCAT('showcase-evidence-', LPAD(MOD(cases.n - 1, 5) + 1, 2, '0'), '.png'),
       CONCAT('uploads/showcase/showcase-evidence-', LPAD(MOD(cases.n - 1, 5) + 1, 2, '0'), '.png'),
       'image/png',
       CASE MOD(cases.n - 1, 5)
           WHEN 0 THEN 15013 WHEN 1 THEN 14850 WHEN 2 THEN 14722 WHEN 3 THEN 14582 ELSE 14469
       END,
       DATE_ADD(complaint.submitted_at, INTERVAL 1 HOUR)
FROM showcase_cases cases
JOIN complaints complaint ON complaint.ticket_number = CONCAT('SHOW-2026-', LPAD(cases.n, 6, '0'))
JOIN students student_profile ON student_profile.student_id = complaint.student_id
WHERE cases.n <= 50
  AND NOT EXISTS (
      SELECT 1 FROM attachments existing
      WHERE existing.complaint_id = complaint.complaint_id
        AND existing.original_name = CONCAT('Showcase evidence ', LPAD(cases.n, 3, '0'), '.png')
  );

SELECT 'showcase seed summary' section;
SELECT 'users' table_name, COUNT(*) row_count FROM users WHERE email LIKE 'showcase.%@example.test'
UNION ALL SELECT 'students', COUNT(*) FROM students WHERE student_number LIKE 'SHOW-STU-%'
UNION ALL SELECT 'staff_profiles', COUNT(*) FROM staff WHERE staff_number LIKE 'SHOW-%'
UNION ALL SELECT 'subcategories', COUNT(*) FROM complaint_subcategories WHERE subcategory_name LIKE 'Showcase issue %'
UNION ALL SELECT 'complaints', COUNT(*) FROM complaints WHERE ticket_number LIKE 'SHOW-2026-%'
UNION ALL SELECT 'assignments', COUNT(*) FROM complaint_assignments WHERE remarks = 'Showcase seed assignment'
UNION ALL SELECT 'history', COUNT(*) FROM complaint_status_history WHERE remarks LIKE 'Showcase seed:%'
UNION ALL SELECT 'notifications', COUNT(*) FROM notifications WHERE title LIKE 'Showcase %'
UNION ALL SELECT 'feedback', COUNT(*) FROM feedback WHERE complaint_id IN (SELECT complaint_id FROM complaints WHERE ticket_number LIKE 'SHOW-2026-%')
UNION ALL SELECT 'attachments', COUNT(*) FROM attachments WHERE original_name LIKE 'Showcase evidence %';
