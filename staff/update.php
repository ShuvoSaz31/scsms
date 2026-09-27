<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Staff");
$id = (int) $_GET["id"];
$q = $pdo->prepare(
    "SELECT c.*,ca.staff_id FROM complaints c JOIN complaint_assignments ca ON ca.complaint_id=c.complaint_id AND ca.unassigned_at IS NULL JOIN staff s ON s.staff_id=ca.staff_id WHERE c.complaint_id=? AND s.user_id=?",
);
$q->execute([$id, $_SESSION["user_id"]]);
$c = $q->fetch();
if (!$c) {
    exit("Access denied.");
}
$err = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf();
    $new = $_POST["status"];
    if (!transition_ok($c["status"], $new)) {
        $err = "Invalid status transition.";
    } elseif ($new === "RESOLVED" && trim($_POST["resolution"]) === "") {
        $err = "Resolution description is required.";
    } else {
        try {
            $pdo->beginTransaction();
            $res = trim($_POST["resolution"]);
            $pdo->prepare(
                'UPDATE complaints SET status=?,resolution_description=CASE WHEN ?<>\'\' THEN ? ELSE resolution_description END,resolved_by=CASE WHEN ?=\'RESOLVED\' THEN ? ELSE resolved_by END,resolved_at=CASE WHEN ?=\'RESOLVED\' THEN NOW() ELSE resolved_at END WHERE complaint_id=?',
            )->execute([
                $new,
                $res,
                $res,
                $new,
                $_SESSION["user_id"],
                $new,
                $id,
            ]);
            add_history(
                $pdo,
                $id,
                $c["status"],
                $new,
                $_SESSION["user_id"],
                trim($_POST["remarks"]),
            );
            $q = $pdo->prepare(
                "SELECT st.user_id FROM complaints c JOIN students st ON st.student_id=c.student_id WHERE c.complaint_id=?",
            );
            $q->execute([$id]);
            $uid = $q->fetchColumn();
            notify(
                $pdo,
                $uid,
                $id,
                "Complaint status changed",
                "Your complaint " .
                    $c["ticket_number"] .
                    " is now " .
                    $new .
                    ".",
            );
            $pdo->commit();
            flash("success", "Complaint updated.");
            header("Location: /scsms_V1/student/complaint.php?id=" . $id);
            exit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $err = "Something went wrong.";
        }
    }
}
$next =
    [
        "SUBMITTED" => "UNDER_REVIEW",
        "ASSIGNED" => "IN_PROGRESS",
        "IN_PROGRESS" => "RESOLVED",
    ][$c["status"]] ?? null;
require "../includes/header.php";
?>
<div class="container">
    <div class="card">
        <h1>Process <?= e($c["ticket_number"]) ?></h1>
        <?= badge($c["status"]) ?>

        <?php if ($err): ?>
            <div class="alert error"><?= e($err) ?></div>
        <?php endif; ?>

        <?php if ($next): ?>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <label>Next Status</label>
                <select name="status">
                    <option><?= $next ?></option>
                </select>

                <label>Remarks</label>
                <textarea name="remarks"></textarea>

                <?php if ($next === "RESOLVED"): ?>
                    <label>Resolution</label>
                    <textarea name="resolution" required></textarea>
                <?php endif; ?>

                <button class="btn">Update</button>
            </form>
        <?php else: ?>
            <p>No staff action is available for this status.</p>
        <?php endif; ?>
    </div>
</div>
<?php require "../includes/footer.php"; ?>
