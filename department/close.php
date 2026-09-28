<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Department Head");
$id = (int) $_GET["id"];
$q = $pdo->prepare(
    "SELECT c.* FROM complaints c JOIN staff h ON h.department_id=c.department_id WHERE c.complaint_id=? AND h.user_id=?",
);
$q->execute([$id, $_SESSION["user_id"]]);
$c = $q->fetch();
if (!$c || $c["status"] !== "RESOLVED") {
    exit("Complaint is not ready to close.");
}
$pdo->beginTransaction();
$pdo->prepare(
    'UPDATE complaints SET status=\'CLOSED\',closed_at=NOW() WHERE complaint_id=?',
)->execute([$id]);
add_history(
    $pdo,
    $id,
    "RESOLVED",
    "CLOSED",
    $_SESSION["user_id"],
    "Complaint closed",
);
$q = $pdo->prepare(
    "SELECT st.user_id FROM complaints c JOIN students st ON st.student_id=c.student_id WHERE c.complaint_id=?",
);
$q->execute([$id]);
notify(
    $pdo,
    $q->fetchColumn(),
    $id,
    "Complaint closed",
    "Your complaint " . $c["ticket_number"] . " has been closed.",
);
$pdo->commit();
flash("success", "Complaint closed.");
header("Location: dashboard.php");
exit();
