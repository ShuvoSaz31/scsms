# SCSMS

Student Complaint Service Management System for submitting, assigning, tracking, and resolving student complaints.

## Requirements

- Windows with XAMPP installed
- Apache and MySQL enabled in the XAMPP Control Panel
- PHP with PDO MySQL and file-info support
- The project directory kept at `C:\xampp\htdocs\scsms_V1` unless the application paths are updated

## Fresh Installation

1. Place the project in `C:\xampp\htdocs\scsms_V1\`.
2. Start Apache and MySQL in XAMPP.
3. Open `http://localhost/phpmyadmin`.
4. Import `database/schema.sql`.
5. Import `database/seed.sql`.
6. Open `http://localhost/scsms_V1/`.

The default database connection is configured in `config/database.php` for a local XAMPP MySQL server using the `root` account without a password. Update it to match your environment when needed.

## Upgrading an Existing Database

Run each applicable migration once in phpMyAdmin, using the `student_service_management` database:

- `database/migration_department_delete.sql` allows a department to be deleted when complaint records refer to it. Apply this before deleting departments if the database predates that change.
- `database/migration_user_contacts.sql` creates email and phone contact tables and copies each user's current email and phone into them. Apply this to enable profile contact management on an existing database.
- `database/migration_profile_photo.sql` adds the profile-photo path used by profile image uploads.
- `database/migration_complaint_ignored.sql` adds the tracked `IGNORED` complaint status for admin and department-head review.

Fresh installations already include these schema changes through `database/schema.sql` and `database/seed.sql`.

## Demo Accounts

All demo accounts use the password `password`.

| Role | Email |
| --- | --- |
| Student | `student@example.com` |
| Staff | `staff@example.com` |
| Staff | `staff2@example.com` |
| Staff | `staff3@example.com` |
| Department Head | `head@example.com` |
| Admin | `admin@example.com` |

Change demo passwords before using these accounts outside a local test environment.

## Showcase Dataset

The default `database/seed.sql` stays small. To add a larger dataset for presentations, run the following from the project directory:

1. Generate the downloadable evidence images and profile avatars in PowerShell:

	```powershell
	.\database\generate_showcase_attachments.ps1
	```

2. Import `database/seed_showcase.sql` into the `student_service_management` database using phpMyAdmin, or run:

	```powershell
	& 'C:\xampp\mysql\bin\mysql.exe' --user=root --default-character-set=utf8mb4 --database=student_service_management --execute="source C:/xampp/htdocs/scsms_V1/database/seed_showcase.sql"
	```

The seed is additive and safe to rerun. It creates 50 students, 50 staff members, 5 department heads, 200 complaints across all statuses and priorities, 64 subcategories, 120 assignments, 540 history events, 260 notifications, 60 feedback records, 50 downloadable attachments, and primary/secondary emails and phone numbers for the showcase accounts. Generated attachments are clearly labeled synthetic evidence.

All showcase accounts use the password `password`:

- Student: `showcase.student001@example.test`
- Staff: `showcase.staff001@example.test`
- Department Head: `showcase.head01@example.test`
- Admin: `admin@example.com`

Roles, departments, and top-level categories remain the application's small fixed lookup catalogs. They are not padded with fake roles or empty departments/categories; sample variety is provided through complaints and subcategories instead.

## User Workflows

### Students

- Submit complaints with a category, department, priority, location, and optional attachment.
- Search and filter complaint history, then follow each complaint's status and activity history.
- Submit one feedback rating and comment after a complaint is resolved or closed.

### Staff

- View complaints assigned to their staff account.
- Update complaint status and enter resolution details when resolving a complaint.

### Department Heads

- Review complaints routed to their department and filter the department queue.
- Assign staff, mark an issue ignored, or mark it resolved during review. Ignore/resolution decisions are recorded in status history and notify the student.
- Assign or reassign active staff members within their department.
- Monitor department workload, unassigned complaints, staff assignments, completed complaints, and average resolution time.
- Close complaints after they are resolved.

### Administrators

- Manage user accounts and their active status.
- Manage departments, complaint categories, and the complaint queue.
- Assign and reassign active staff members to complaints.

### Profiles

- Update the profile name and change the password after confirming the current password.
- Add, update, make primary, and delete email addresses and phone numbers.
- At least one email address and one phone number must remain on each profile.
- Any saved email address can be used to sign in; the primary email is shown in the legacy account field.

## Complaint Lifecycle

Complaints can move through these statuses:

`SUBMITTED` -> `UNDER_REVIEW` -> `ASSIGNED` -> `IN_PROGRESS` -> `RESOLVED` -> `CLOSED`

Assignment can move a new or under-review complaint directly to `ASSIGNED`. Admins and department heads can also mark an actionable complaint `IGNORED` during review. `IGNORED` is a tracked terminal state, separate from `CLOSED`; the reviewer and decision note are saved in complaint history. Each status change is recorded in the complaint history. Students can view that history from the complaint details page.

## Attachments

Accepted file types are PDF, PNG, JPG/JPEG, DOC, and DOCX. Files must be 5 MB or smaller. The application checks both the extension and detected MIME type before saving an upload.

## Security and Configuration

- Role-based access checks protect role-specific pages.
- Forms use CSRF tokens and database queries use prepared statements.
- Passwords are stored using PHP's password hashing functions.
- Configure database credentials in `config/database.php`.
- Application links use the `/scsms_V1/` base path. If the project folder name changes, update those paths in the PHP files as well.

## Project Notes

The application includes role-specific dashboards, complaint search and filtering, notifications, feedback, status history, department assignment, secure uploads, and profile management. The existing interface is styled for desktop and mobile layouts.