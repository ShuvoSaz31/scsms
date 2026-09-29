<?php
require_once __DIR__ . "/../includes/functions.php";
require_role("Department Head");

$q = $pdo->prepare(
    "SELECT s.department_id,d.department_name FROM staff s LEFT JOIN departments d ON d.department_id=s.department_id WHERE s.user_id=? AND s.status='ACTIVE'",
);
$q->execute([$_SESSION["user_id"]]);
$department = $q->fetch();
if (!$department || !$department["department_id"]) {
    exit("Department Head profile not found.");
}
$departmentId = (int) $department["department_id"];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf();
    try {
        $message = review_complaint(
            $pdo,
            (int) ($_POST["complaint_id"] ?? 0),
            $_SESSION["user_id"],
            $_POST["action"] ?? "",
            $departmentId,
            $_POST["note"] ?? "",
        );
        flash("success", $message);
    } catch (Throwable $e) {
        flash("error", $e->getMessage());
    }
    header("Location: complaints.php");
    exit();
}

$where = ["c.department_id=?"];
$args = [$departmentId];
$query = trim($_GET["q"] ?? "");
if ($query !== "") {
    $where[] = "(c.ticket_number LIKE ? OR c.title LIKE ? OR st.student_number LIKE ? OR su.full_name LIKE ?)";
    $like = "%" . $query . "%";
    array_push($args, $like, $like, $like, $like);
}
$statuses = ["SUBMITTED", "UNDER_REVIEW", "ASSIGNED", "IN_PROGRESS", "RESOLVED", "CLOSED", "IGNORED"];
$status = $_GET["status"] ?? "";
if (in_array($status, $statuses, true)) {
    $where[] = "c.status=?";
    $args[] = $status;
} else {
    $status = "";
}

$q = $pdo->prepare(
    "SELECT c.complaint_id,c.ticket_number,c.title,c.status,c.priority,c.submitted_at,st.student_number,su.full_name student_name,(SELECT u.full_name FROM complaint_assignments ca JOIN staff s ON s.staff_id=ca.staff_id JOIN users u ON u.user_id=s.user_id WHERE ca.complaint_id=c.complaint_id AND ca.unassigned_at IS NULL ORDER BY ca.assignment_id DESC LIMIT 1) assigned_staff FROM complaints c JOIN students st ON st.student_id=c.student_id JOIN users su ON su.user_id=st.user_id WHERE " . implode(" AND ", $where) . " ORDER BY c.submitted_at DESC",
);
$q->execute($args);
$rows = $q->fetchAll();

require "../includes/header.php";
?>
<div class="container admin-complaints">
    <div class="page-heading">
        <div>
            <p class="eyebrow">DEPARTMENT SERVICES</p>
            <h1><?= e($department["department_name"]) ?> Complaints</h1>
            <p class="muted">Review and assign every complaint routed to your department.</p>
        </div>
    </div>
    <section class="card complaint-filters">
        <form method="get">
            <input name="q" placeholder="Ticket, title, student ID or name" value="<?= e($query) ?>">
            <select name="status">
                <option value="">All statuses</option>
                <?php foreach ($statuses as $option): ?>
                    <option value="<?= e($option) ?>" <?= $status === $option ? "selected" : "" ?>><?= e(str_replace("_", " ", $option)) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn">Filter</button>
            <a class="btn secondary" href="complaints.php">Clear</a>
        </form>
    </section>
    <section class="card complaint-table">
        <div class="table-scroll">
            <table>
                <thead><tr><th>Ticket</th><th>Student</th><th>Complaint</th><th>Priority</th><th>Status</th><th>Assigned staff</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><a href="/scsms_V1/student/complaint.php?id=<?= (int) $row["complaint_id"] ?>"><?= e($row["ticket_number"]) ?></a></td>
                            <td><?= e($row["student_number"] . " - " . $row["student_name"]) ?></td>
                            <td><strong><?= e($row["title"]) ?></strong><span class="muted ticket-category"><?= e($row["submitted_at"]) ?></span></td>
                            <td><?= e($row["priority"]) ?></td>
                            <td><?= badge($row["status"]) ?></td>
                            <td><?= e($row["status"] === "IGNORED" ? "Released" : ($row["assigned_staff"] ?? "Unassigned")) ?></td>
                            <td>
                                <?php if (in_array($row["status"], ["SUBMITTED", "UNDER_REVIEW", "ASSIGNED", "IN_PROGRESS"], true)): ?>
                                    <div class="department-review-actions">
                                        <a class="btn" href="assign.php?id=<?= (int) $row["complaint_id"] ?>"><?= $row["assigned_staff"] ? "Reassign" : "Assign" ?></a>
                                        <form class="review-decision-form" method="post" onsubmit="return confirm('Mark this complaint as ignored?')">
                                            <input type="hidden" name="csrf" value="<?= csrf() ?>">
                                            <input type="hidden" name="complaint_id" value="<?= (int) $row["complaint_id"] ?>">
                                            <input type="hidden" name="action" value="ignore">
                                            <input name="note" maxlength="500" placeholder="Ignore note (optional)" aria-label="Reason for ignoring <?= e($row["ticket_number"]) ?>">
                                            <button class="btn danger">Ignore</button>
                                        </form>
                                        <form class="review-decision-form" method="post">
                                            <input type="hidden" name="csrf" value="<?= csrf() ?>">
                                            <input type="hidden" name="complaint_id" value="<?= (int) $row["complaint_id"] ?>">
                                            <input type="hidden" name="action" value="resolve">
                                            <input name="note" maxlength="500" placeholder="Resolution note (optional)" aria-label="Resolution note for <?= e($row["ticket_number"]) ?>">
                                            <button class="btn secondary">Mark resolved</button>
                                        </form>
                                    </div>
                                <?php elseif ($row["status"] === "RESOLVED"): ?>
                                    <a class="btn secondary" href="close.php?id=<?= (int) $row["complaint_id"] ?>">Close</a>
                                <?php else: ?>
                                    <span class="muted">No action</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?><tr><td class="empty-state" colspan="7">No complaints match these filters.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require "../includes/footer.php"; ?>
