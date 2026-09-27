USE student_service_management;

INSERT INTO roles (role_name, description)
VALUES
    ("Student", "Student"),
    ("Staff", "Staff"),
    ("Department Head", "Department Head"),
    ("Admin", "Administrator");

INSERT INTO departments (department_name, description)
VALUES
    ("CSE", "Computer Science and Engineering"),
    ("EEE", "Electrical and Electronic Engineering"),
    ("IT", "Information Technology"),
    ("Facilities", "Facilities"),
    ("Administration", "Administration");

INSERT INTO complaint_categories (category_name)
VALUES
    ("Academic"),
    ("IT & Internet"),
    ("Facilities"),
    ("Laboratory"),
    ("Transport"),
    ("Library"),
    ("Security"),
    ("Administration");

INSERT INTO complaint_subcategories (category_id, subcategory_name)
SELECT category_id, "Wi-Fi"
FROM complaint_categories
WHERE category_name = "IT & Internet";

INSERT INTO complaint_subcategories (category_id, subcategory_name)
SELECT category_id, "Network"
FROM complaint_categories
WHERE category_name = "IT & Internet";

INSERT INTO complaint_subcategories (category_id, subcategory_name)
SELECT category_id, "Computer"
FROM complaint_categories
WHERE category_name = "IT & Internet";

INSERT INTO users (email, password_hash, full_name, phone)
VALUES
    ("student@example.com", "$2y$12$k/9CclitssQeGBya0DCQfOHBksyw8afxRs.b0qqZppBYdVddT3RFC", "Demo Student", "01700000000"),
    ("staff@example.com", "$2y$12$k/9CclitssQeGBya0DCQfOHBksyw8afxRs.b0qqZppBYdVddT3RFC", "Demo Staff", "01700000001"),
    ("head@example.com", "$2y$12$k/9CclitssQeGBya0DCQfOHBksyw8afxRs.b0qqZppBYdVddT3RFC", "Department Head", "01700000002"),
    ("admin@example.com", "$2y$12$k/9CclitssQeGBya0DCQfOHBksyw8afxRs.b0qqZppBYdVddT3RFC", "System Admin", "01700000003");

INSERT INTO user_roles
SELECT u.user_id, r.role_id
FROM users u
JOIN roles r ON (
    (u.email = "student@example.com" AND r.role_name = "Student") OR
    (u.email = "staff@example.com" AND r.role_name = "Staff") OR
    (u.email = "head@example.com" AND r.role_name = "Department Head") OR
    (u.email = "admin@example.com" AND r.role_name = "Admin")
);

INSERT INTO students (user_id, student_number, department_id, program, trimester)
SELECT u.user_id, "STU-001", d.department_id, "BSc in CSE", "12th"
FROM users u
JOIN departments d ON d.department_name = "CSE"
WHERE u.email = "student@example.com";

INSERT INTO staff (user_id, staff_number, department_id, designation)
SELECT u.user_id, "STF-001", d.department_id, "Network Administrator"
FROM users u
JOIN departments d ON d.department_name = "IT"
WHERE u.email = "staff@example.com";

INSERT INTO staff (user_id, staff_number, department_id, designation)
SELECT u.user_id, "DH-001", d.department_id, "Department Head"
FROM users u
JOIN departments d ON d.department_name = "IT"
WHERE u.email = "head@example.com";
