<?php require_once __DIR__ . "/functions.php"; ?>
<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SCSMS</title><link rel="stylesheet" href="/scsms_V1/assets/style.css"></head><body>
<header><div class="brand">SCSMS</div><?php if (
    !empty($_SESSION["user_id"])
): ?><nav><a href="<?= home() ?>">Dashboard</a><?php if (
    $_SESSION["role"] === "Student"
): ?><a href="/scsms_V1/student/create_complaint.php">New Complaint</a><a href="/scsms_V1/student/complaints.php">My Complaints</a><?php elseif (
    $_SESSION["role"] === "Staff"
): ?><a href="/scsms_V1/staff/dashboard.php">Assigned</a><?php elseif (
    $_SESSION["role"] === "Department Head"
): ?><a href="/scsms_V1/department/dashboard.php">Department</a><?php else: ?><a href="/scsms_V1/admin/complaints.php">Complaints</a><a href="/scsms_V1/admin/users.php">Users</a><a href="/scsms_V1/admin/departments.php">Departments</a><a href="/scsms_V1/admin/categories.php">Categories</a><?php endif; ?><a href="/scsms_V1/notifications.php">Notifications</a><a href="/scsms_V1/profile.php">Profile</a><a href="/scsms_V1/logout.php">Logout</a></nav><?php endif; ?></header><main><?php show_flash(); ?>
