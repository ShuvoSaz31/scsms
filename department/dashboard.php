<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Department Head");
$q = $pdo->prepare("SELECT staff_id,department_id FROM staff WHERE user_id=?");
$q->execute([$_SESSION["user_id"]]);
$me = $q->fetch();
if (!$me) {
    exit("Department Head profile not found.");
}
$q = $pdo->prepare(
    "SELECT c.*,d.department_name,st.student_number,u.full_name student_name,(SELECT u2.full_name FROM complaint_assignments ca2 JOIN staff s2 ON s2.staff_id=ca2.staff_id JOIN users u2 ON u2.user_id=s2.user_id WHERE ca2.complaint_id=c.complaint_id AND ca2.unassigned_at IS NULL LIMIT 1) assigned_staff FROM complaints c JOIN students st ON st.student_id=c.student_id JOIN users u ON u.user_id=st.user_id JOIN departments d ON d.department_id=c.department_id WHERE c.department_id=? ORDER BY c.submitted_at DESC",
);
$q->execute([$me["department_id"]]);
$rows = $q->fetchAll();
require "../includes/header.php";
?><div class="container"><h1>Department Head Dashboard</h1><div class="card"><table><tr><th>Ticket</th><th>Student</th><th>Title</th><th>Status</th><th>Assigned</th><th>Action</th></tr><?php foreach (
    $rows
    as $r
): ?><tr><td><?= e($r["ticket_number"]) ?></td><td><?= e(
    $r["student_number"],
) ?></td><td><?= e($r["title"]) ?></td><td><?= badge(
    $r["status"],
) ?></td><td><?= e(
    $r["assigned_staff"] ?? "Unassigned",
) ?></td><td><a class="btn" href="assign.php?id=<?= $r[
    "complaint_id"
] ?>">Assign / Reassign</a><?php if (
    $r["status"] === "RESOLVED"
): ?> <a class="btn secondary" href="close.php?id=<?= $r[
     "complaint_id"
 ] ?>">Close</a><?php endif; ?></td></tr><?php endforeach; ?></table></div></div><?php require "../includes/footer.php"; ?>
