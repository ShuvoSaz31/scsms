SCSMS COMPLETE - XAMPP INSTALLATION

1. Extract this project into C:\xampp\htdocs\scsms_V1\ (the folder name is required by the app's links).
2. Start Apache and MySQL in XAMPP.
3. Open http://localhost/phpmyadmin
4. Import database/schema.sql first.
5. Import database/seed.sql second.
6. Open http://localhost/scsms_V1/

DEMO ACCOUNTS (password: password)
Student: student@example.com
Staff: staff@example.com
Staff: staff2@example.com
Staff: staff3@example.com
Department Head: head@example.com
Admin: admin@example.com

Level 1 features included:
Authentication, RBAC, student/staff profiles, departments, categories/subcategories,
complaint submission, unique ticket number, priorities, assignment/reassignment,
status lifecycle, history, resolution, closure, secure attachments, notifications,
feedback, search/filtering, role dashboards, admin management, prepared statements,
CSRF protection, password hashing, transactions and upload validation.

ADMIN COMPLAINT ASSIGNMENT
Open Complaints from the Admin navigation. New and under-review complaints can be
assigned to active staff in the complaint's department. Existing assignments can
be reassigned from the same list. Assignment updates the complaint status and
history, and sends notifications to the assigned staff member and student.

Complaint forms and the admin complaint list are styled for responsive use on
desktop and mobile screens.

Attachment types: PDF, PNG, JPG/JPEG, DOC, DOCX. Maximum 5 MB.

Keep the project folder named scsms_V1. If you change it, update the
/scsms_V1/ paths in the PHP files before opening the application.
