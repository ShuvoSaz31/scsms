<?php
require_once __DIR__ . "/../includes/functions.php";
require_role("Department Head");

$id = (int) ($_GET["id"] ?? $_POST["complaint_id"] ?? 0);
$q = $pdo->prepare(
    "SELECT s.department_id FROM staff s WHERE s.user_id=? AND s.status='ACTIVE'",
);
$q->execute([$_SESSION["user_id"]]);
$departmentId = (int) $q->fetchColumn();
if (!$departmentId) {
    exit("Department Head profile not found.");
}

$q = $pdo->prepare(
    "SELECT c.*,st.user_id student_user FROM complaints c JOIN students st ON st.student_id=c.student_id WHERE c.complaint_id=? AND c.department_id=?",
);
$q->execute([$id, $departmentId]);
$c = $q->fetch();
if (!$c || !in_array($c["status"], ["SUBMITTED", "UNDER_REVIEW", "ASSIGNED", "IN_PROGRESS"], true)) {
    exit("This complaint is not available for assignment.");
}

$q = $pdo->prepare(
    "SELECT s.staff_id,u.full_name,s.designation FROM staff s JOIN users u ON u.user_id=s.user_id WHERE s.department_id=? AND s.status='ACTIVE' AND u.is_active=1 ORDER BY u.full_name",
);
$q->execute([$departmentId]);
$staff = $q->fetchAll();
$q = $pdo->prepare(
    "SELECT ca.staff_id FROM complaint_assignments ca WHERE ca.complaint_id=? AND ca.unassigned_at IS NULL ORDER BY ca.assignment_id DESC LIMIT 1",
);
$q->execute([$id]);
$currentStaffId = $q->fetchColumn();
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf();
    try {
        $pdo->beginTransaction();
        $q = $pdo->prepare(
            "SELECT c.*,st.user_id student_user FROM complaints c JOIN students st ON st.student_id=c.student_id WHERE c.complaint_id=? AND c.department_id=? FOR UPDATE",
        );
        $q->execute([$id, $departmentId]);
        $c = $q->fetch();
        if (!$c || !in_array($c["status"], ["SUBMITTED", "UNDER_REVIEW", "ASSIGNED", "IN_PROGRESS"], true)) {
            throw new Exception("This complaint is no longer available for assignment.");
        }

        $staffId = (int) ($_POST["staff_id"] ?? 0);
        $q = $pdo->prepare(
            "SELECT s.staff_id,u.user_id,u.full_name FROM staff s JOIN users u ON u.user_id=s.user_id WHERE s.staff_id=? AND s.department_id=? AND s.status='ACTIVE' AND u.is_active=1",
        );
        $q->execute([$staffId, $departmentId]);
        $assignee = $q->fetch();
        if (!$assignee) {
            throw new Exception("Choose an active staff member in your department.");
        }

        $pdo->prepare(
            "UPDATE complaint_assignments SET unassigned_at=NOW() WHERE complaint_id=? AND unassigned_at IS NULL",
        )->execute([$id]);
        $pdo->prepare(
            "INSERT INTO complaint_assignments(complaint_id,staff_id,assigned_by,remarks) VALUES(?,?,?,?)",
        )->execute([$id, $staffId, $_SESSION["user_id"], trim($_POST["remarks"] ?? "")]);

        if (in_array($c["status"], ["SUBMITTED", "UNDER_REVIEW"], true)) {
            $pdo->prepare(
                "UPDATE complaints SET status='ASSIGNED' WHERE complaint_id=?",
            )->execute([$id]);
            add_history($pdo, $id, $c["status"], "ASSIGNED", $_SESSION["user_id"], "Assigned to " . $assignee["full_name"]);
        }

        notify($pdo, $assignee["user_id"], $id, "Complaint assigned", "Complaint " . $c["ticket_number"] . " has been assigned to you.");
        notify($pdo, $c["student_user"], $id, "Complaint assigned", "Your complaint " . $c["ticket_number"] . " has been assigned to " . $assignee["full_name"] . ".");
        $pdo->commit();
        flash("success", "Complaint assigned to " . $assignee["full_name"] . ".");
        header("Location: complaints.php");
        exit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $err = $e->getMessage();
    }
}

require "../includes/header.php";
?>
<div class="container">
    <div class="card">
        <h1><?= $currentStaffId ? "Reassign" : "Assign" ?> Complaint</h1>
        <p><b><?= e($c["ticket_number"]) ?></b> - <?= e($c["title"]) ?></p>
        <?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>
        <?php if (!$staff): ?>
            <p class="empty-state">There are no active staff members in your department to assign this complaint to.</p>
        <?php else: ?>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <input type="hidden" name="complaint_id" value="<?= $id ?>">
                <label for="staff-id">Staff member</label>
                <select id="staff-id" name="staff_id" required>
                    <?php foreach ($staff as $member): ?>
                        <option value="<?= (int) $member["staff_id"] ?>" <?= (string) $currentStaffId === (string) $member["staff_id"] ? "selected" : "" ?>><?= e($member["full_name"] . ($member["designation"] ? " - " . $member["designation"] : "")) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="remarks">Assignment note</label>
                <textarea id="remarks" name="remarks" maxlength="500"></textarea>
                <button class="btn"><?= $currentStaffId ? "Reassign" : "Assign" ?></button>
                <a class="btn secondary" href="complaints.php">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</div>
<?php require "../includes/footer.php"; ?>
