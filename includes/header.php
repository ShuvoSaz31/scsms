<?php require_once __DIR__ . "/functions.php"; ?>
<?php
$hasSidebar = !empty($_SESSION["user_id"]);
$currentPage = basename($_SERVER["PHP_SELF"] ?? "");
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>SCSMS</title>
    <link rel="stylesheet" href="/scsms_V1/assets/style.css">
    <link rel="stylesheet" href="/scsms_V1/assets/dashboard.css">
    <?php if (in_array($currentPage, ["profile.php", "profile_edit.php"], true)): ?>
        <link rel="stylesheet" href="/scsms_V1/assets/profile.css">
    <?php endif; ?>
</head>
<body class="<?= $hasSidebar ? "has-sidebar" : "is-guest" ?>">
<?php if ($hasSidebar): ?>
    <input class="sidebar-toggle-state" type="checkbox" id="sidebar-toggle" aria-label="Toggle navigation">
    <aside class="app-sidebar" id="app-sidebar">
        <a class="sidebar-brand" href="<?= home() ?>">
            <span class="sidebar-brand-mark">S</span>
            <span class="sidebar-brand-copy"><strong>SCSMS</strong><small>STUDENT SERVICES</small></span>
        </a>
        <div class="sidebar-account">
            <span class="sidebar-avatar"><?= e(strtoupper(substr($_SESSION["name"] ?? "U", 0, 1))) ?></span>
            <span class="sidebar-account-copy"><strong><?= e($_SESSION["name"] ?? "Account") ?></strong><small><?= e($_SESSION["role"] ?? "User") ?></small></span>
        </div>
        <p class="sidebar-section-label">Workspace</p>
        <nav class="sidebar-nav" aria-label="Main navigation">
            <a class="sidebar-link <?= $currentPage === "dashboard.php" ? "is-active" : "" ?>" href="<?= home() ?>" title="<?= $_SESSION["role"] === "Staff" ? "Assigned cases" : "Dashboard" ?>"><span class="sidebar-link-icon" aria-hidden="true">▦</span><span class="sidebar-link-label"><?= $_SESSION["role"] === "Staff" ? "Assigned cases" : "Dashboard" ?></span></a>
            <?php if ($_SESSION["role"] === "Student"): ?>
                <a class="sidebar-link <?= $currentPage === "create_complaint.php" ? "is-active" : "" ?>" href="/scsms_V1/student/create_complaint.php" title="New complaint"><span class="sidebar-link-icon" aria-hidden="true">＋</span><span class="sidebar-link-label">New complaint</span></a>
                <a class="sidebar-link <?= $currentPage === "complaints.php" ? "is-active" : "" ?>" href="/scsms_V1/student/complaints.php" title="My complaints"><span class="sidebar-link-icon" aria-hidden="true">▤</span><span class="sidebar-link-label">My complaints</span></a>
            <?php elseif ($_SESSION["role"] === "Department Head"): ?>
                <a class="sidebar-link <?= $currentPage === "complaints.php" || $currentPage === "assign.php" ? "is-active" : "" ?>" href="/scsms_V1/department/complaints.php" title="Department complaints"><span class="sidebar-link-icon" aria-hidden="true">▤</span><span class="sidebar-link-label">Department complaints</span></a>
                <a class="sidebar-link <?= $currentPage === "performance.php" ? "is-active" : "" ?>" href="/scsms_V1/department/performance.php" title="Performance"><span class="sidebar-link-icon" aria-hidden="true">◫</span><span class="sidebar-link-label">Performance</span></a>
            <?php elseif ($_SESSION["role"] === "Staff"): ?>
            <?php else: ?>
                <a class="sidebar-link <?= $currentPage === "complaints.php" ? "is-active" : "" ?>" href="/scsms_V1/admin/complaints.php" title="Complaints"><span class="sidebar-link-icon" aria-hidden="true">▤</span><span class="sidebar-link-label">Complaints</span></a>
                <a class="sidebar-link <?= $currentPage === "users.php" ? "is-active" : "" ?>" href="/scsms_V1/admin/users.php" title="Users"><span class="sidebar-link-icon" aria-hidden="true">♙</span><span class="sidebar-link-label">Users</span></a>
                <a class="sidebar-link <?= $currentPage === "departments.php" ? "is-active" : "" ?>" href="/scsms_V1/admin/departments.php" title="Departments"><span class="sidebar-link-icon" aria-hidden="true">⌂</span><span class="sidebar-link-label">Departments</span></a>
                <a class="sidebar-link <?= $currentPage === "categories.php" ? "is-active" : "" ?>" href="/scsms_V1/admin/categories.php" title="Categories"><span class="sidebar-link-icon" aria-hidden="true">▧</span><span class="sidebar-link-label">Categories</span></a>
            <?php endif; ?>
        </nav>
        <div class="sidebar-bottom">
            <a class="sidebar-link <?= $currentPage === "notifications.php" ? "is-active" : "" ?>" href="/scsms_V1/notifications.php" title="Notifications"><span class="sidebar-link-icon" aria-hidden="true">◉</span><span class="sidebar-link-label">Notifications</span></a>
            <a class="sidebar-link <?= in_array($currentPage, ["profile.php", "profile_edit.php"], true) ? "is-active" : "" ?>" href="/scsms_V1/profile.php" title="Profile"><span class="sidebar-link-icon" aria-hidden="true">⚙</span><span class="sidebar-link-label">Profile</span></a>
            <a class="sidebar-link sidebar-logout" href="/scsms_V1/logout.php" title="Sign out"><span class="sidebar-link-icon" aria-hidden="true">↪</span><span class="sidebar-link-label">Sign out</span></a>
        </div>
    </aside>
    <label class="sidebar-backdrop" for="sidebar-toggle" aria-label="Close navigation"></label>
<?php endif; ?>
<div class="app-main">
    <header class="app-topbar">
        <?php if ($hasSidebar): ?>
            <div class="topbar-navigation-controls">
                <label class="sidebar-toggle-button sidebar-expand-button" for="sidebar-toggle" aria-label="Open navigation" title="Open navigation">
                    <span class="sidebar-toggle-glyph" aria-hidden="true"><i></i><i></i><i></i></span>
                    <span class="sidebar-toggle-text">Menu</span>
                </label>
                <label class="sidebar-collapse-button" for="sidebar-toggle" aria-label="Collapse navigation" title="Collapse navigation">
                    <span class="sidebar-collapse-glyph" aria-hidden="true"><i></i></span>
                </label>
            </div>
            <span class="topbar-context">SCSMS <span>/</span> <?= e($_SESSION["role"] ?? "Workspace") ?></span>
        <?php else: ?>
            <a class="guest-brand" href="/scsms_V1/index.php">SCSMS</a>
        <?php endif; ?>
    </header>
<main><?php show_flash(); ?>
