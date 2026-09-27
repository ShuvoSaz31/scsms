<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Student");
$where = "st.user_id=?";
$args = [$_SESSION["user_id"]];
if (!empty($_GET["q"])) {
    $where .= " AND (c.ticket_number LIKE ? OR c.title LIKE ?)";
    $args[] = "%" . $_GET["q"] . "%";
    $args[] = "%" . $_GET["q"] . "%";
}
if (!empty($_GET["status"])) {
    $where .= " AND c.status=?";
    $args[] = $_GET["status"];
}
$q = $pdo->prepare(
    "SELECT c.*,d.department_name FROM complaints c JOIN students st ON st.student_id=c.student_id JOIN departments d ON d.department_id=c.department_id WHERE $where ORDER BY c.submitted_at DESC",
);
$q->execute($args);
$rows = $q->fetchAll();
require "../includes/header.php";
?><div class="container"><div class="card"><h1>My Complaints</h1><form><input name="q" placeholder="Search ticket/title" value="<?= e(
    $_GET["q"] ?? "",
) ?>"><select name="status"><option value="">All Status</option><?php foreach (
    [
        "SUBMITTED",
        "UNDER_REVIEW",
        "ASSIGNED",
        "IN_PROGRESS",
        "RESOLVED",
        "CLOSED",
    ]
    as $x
): ?><option <?= $x === ($_GET["status"] ?? "")
    ? "selected"
    : "" ?>><?= $x ?></option><?php endforeach; ?></select><button class="btn">Search</button></form></div><div class="card"><table><tr><th>Ticket</th><th>Title</th><th>Department</th><th>Priority</th><th>Status</th></tr><?php foreach (
    $rows
    as $r
): ?><tr><td><a href="complaint.php?id=<?= $r["complaint_id"] ?>"><?= e(
    $r["ticket_number"],
) ?></a></td><td><?= e($r["title"]) ?></td><td><?= e(
    $r["department_name"],
) ?></td><td><?= e($r["priority"]) ?></td><td><?= badge(
    $r["status"],
) ?></td></tr><?php endforeach; ?></table></div></div><?php require "../includes/footer.php"; ?>
