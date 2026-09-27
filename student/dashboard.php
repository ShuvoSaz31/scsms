<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Student");
$q = $pdo->prepare(
    'SELECT COUNT(*) total,SUM(status IN (\'SUBMITTED\',\'UNDER_REVIEW\',\'ASSIGNED\')) pending,SUM(status=\'IN_PROGRESS\') progress,SUM(status=\'RESOLVED\') resolved,SUM(status=\'CLOSED\') closed FROM complaints WHERE student_id=(SELECT student_id FROM students WHERE user_id=?)',
);
$q->execute([$_SESSION["user_id"]]);
$s = $q->fetch();
$q = $pdo->prepare(
    "SELECT c.*,d.department_name FROM complaints c JOIN students st ON st.student_id=c.student_id JOIN departments d ON d.department_id=c.department_id WHERE st.user_id=? ORDER BY c.submitted_at DESC LIMIT 5",
);
$q->execute([$_SESSION["user_id"]]);
$rows = $q->fetchAll();
require "../includes/header.php";
?><div class="container"><h1>Student Dashboard</h1><div class="grid"><div class="stat">Total<b><?= $s[
    "total"
] ?? 0 ?></b></div><div class="stat">Pending<b><?= $s["pending"] ??
    0 ?></b></div><div class="stat">In Progress<b><?= $s["progress"] ??
    0 ?></b></div><div class="stat">Resolved<b><?= $s["resolved"] ??
    0 ?></b></div><div class="stat">Closed<b><?= $s["closed"] ??
    0 ?></b></div></div><div class="card"><a class="btn" href="create_complaint.php">Submit New Complaint</a></div><div class="card"><h2>Recent Complaints</h2><table><tr><th>Ticket</th><th>Title</th><th>Status</th><th>Priority</th></tr><?php foreach (
    $rows
    as $r
): ?><tr><td><a href="complaint.php?id=<?= $r["complaint_id"] ?>"><?= e(
    $r["ticket_number"],
) ?></a></td><td><?= e($r["title"]) ?></td><td><?= badge(
    $r["status"],
) ?></td><td><?= e(
    $r["priority"],
) ?></td></tr><?php endforeach; ?></table></div></div><?php require "../includes/footer.php"; ?>
