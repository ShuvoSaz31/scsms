<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Staff");
$q = $pdo->prepare("SELECT staff_id FROM staff WHERE user_id=?");
$q->execute([$_SESSION["user_id"]]);
$sid = $q->fetchColumn();
$q = $pdo->prepare(
    "SELECT c.*,d.department_name,st.student_number,u.full_name student_name FROM complaints c JOIN complaint_assignments ca ON ca.complaint_id=c.complaint_id AND ca.unassigned_at IS NULL JOIN staff s ON s.staff_id=ca.staff_id JOIN students st ON st.student_id=c.student_id JOIN users u ON u.user_id=st.user_id JOIN departments d ON d.department_id=c.department_id WHERE s.staff_id=? ORDER BY c.updated_at DESC",
);
$q->execute([$sid]);
$rows = $q->fetchAll();
require "../includes/header.php";
?><div class="container"><h1>Staff Dashboard</h1><div class="card"><table><tr><th>Ticket</th><th>Student</th><th>Title</th><th>Priority</th><th>Status</th><th>Action</th></tr><?php foreach (
    $rows
    as $r
): ?><tr><td><a href="/scsms_V1/student/complaint.php?id=<?= $r[
    "complaint_id"
] ?>"><?= e($r["ticket_number"]) ?></a></td><td><?= e(
    $r["student_number"],
) ?></td><td><?= e($r["title"]) ?></td><td><?= e(
    $r["priority"],
) ?></td><td><?= badge(
    $r["status"],
) ?></td><td><a class="btn" href="update.php?id=<?= $r[
    "complaint_id"
] ?>">Process</a></td></tr><?php endforeach; ?></table></div></div><?php require "../includes/footer.php"; ?>
