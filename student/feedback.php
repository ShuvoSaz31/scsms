<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Student");
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    exit();
}
check_csrf();
$id = (int) $_POST["complaint_id"];
$q = $pdo->prepare(
    "SELECT c.complaint_id,st.student_id,c.status FROM complaints c JOIN students st ON st.student_id=c.student_id WHERE c.complaint_id=? AND st.user_id=?",
);
$q->execute([$id, $_SESSION["user_id"]]);
$c = $q->fetch();
if (!$c || $c["status"] !== "RESOLVED") {
    exit("Feedback is not available.");
}
$q = $pdo->prepare("SELECT feedback_id FROM feedback WHERE complaint_id=?");
$q->execute([$id]);
if ($q->fetch()) {
    exit("Feedback already submitted.");
}
$pdo->prepare(
    "INSERT INTO feedback(complaint_id,student_id,rating,comment) VALUES(?,?,?,?)",
)->execute([$id, $c["student_id"], $_POST["rating"], trim($_POST["comment"])]);
notify(
    $pdo,
    1,
    $id,
    "Feedback received",
    "A student submitted feedback for complaint #" . $id . ".",
);
flash("success", "Feedback submitted successfully.");
header("Location: complaint.php?id=" . $id);
exit();
