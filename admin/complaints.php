<?php
require_once __DIR__ . "/../includes/functions.php";
require_role("Admin");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf();
    $complaintId = (int) ($_POST["complaint_id"] ?? 0);
    $staffId = (int) ($_POST["staff_id"] ?? 0);

    try {
        $pdo->beginTransaction();
        $q = $pdo->prepare(
            "SELECT c.*,st.user_id student_user FROM complaints c JOIN students st ON st.student_id=c.student_id WHERE c.complaint_id=? FOR UPDATE",
        );
        $q->execute([$complaintId]);
        $complaint = $q->fetch();
        if (!$complaint) {
            throw new Exception("Complaint not found.");
        }
        if (!in_array($complaint["status"], ["SUBMITTED", "UNDER_REVIEW", "ASSIGNED", "IN_PROGRESS"], true)) {
            throw new Exception("This complaint can no longer be assigned.");
        }

        $q = $pdo->prepare(
            "SELECT s.staff_id,u.user_id,u.full_name,d.department_name FROM staff s JOIN users u ON u.user_id=s.user_id LEFT JOIN departments d ON d.department_id=s.department_id WHERE s.staff_id=? AND s.status='ACTIVE' AND u.is_active=1",
        );
        $q->execute([$staffId]);
        $assignee = $q->fetch();
        if (!$assignee) {
            throw new Exception("Choose an active staff member.");
        }

        $pdo->prepare(
            "UPDATE complaint_assignments SET unassigned_at=NOW() WHERE complaint_id=? AND unassigned_at IS NULL",
        )->execute([$complaintId]);
        $pdo->prepare(
            "INSERT INTO complaint_assignments(complaint_id,staff_id,assigned_by,remarks) VALUES(?,?,?,?)",
        )->execute([
            $complaintId,
            $staffId,
            $_SESSION["user_id"],
            trim($_POST["remarks"] ?? ""),
        ]);

        if (in_array($complaint["status"], ["SUBMITTED", "UNDER_REVIEW"], true)) {
            if (!transition_ok($complaint["status"], "ASSIGNED")) {
                throw new Exception("Complaint cannot be assigned from its current status.");
            }
            $pdo->prepare(
                "UPDATE complaints SET status='ASSIGNED' WHERE complaint_id=?",
            )->execute([$complaintId]);
            add_history(
                $pdo,
                $complaintId,
                $complaint["status"],
                "ASSIGNED",
                $_SESSION["user_id"],
                "Assigned to " . $assignee["full_name"],
            );
        }

        notify(
            $pdo,
            $assignee["user_id"],
            $complaintId,
            "Complaint assigned",
            "Complaint " . $complaint["ticket_number"] . " has been assigned to you.",
        );
        notify(
            $pdo,
            $complaint["student_user"],
            $complaintId,
            "Complaint assigned",
            "Your complaint " . $complaint["ticket_number"] . " has been assigned to " . $assignee["full_name"] . ".",
        );
        $pdo->commit();
        flash("success", "Complaint assigned to " . $assignee["full_name"] . ".");
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        flash("error", $e->getMessage());
    }

    header("Location: complaints.php");
    exit();
}

$where = "1=1";
$args = [];
if ($_GET["q"] ?? "") {
    $where .=
        " AND (c.ticket_number LIKE ? OR c.title LIKE ? OR st.student_number LIKE ? OR su.full_name LIKE ?)";
    $value = "%" . $_GET["q"] . "%";
    $args = array_merge($args, [$value, $value, $value, $value]);
}
foreach (["status", "priority", "department_id", "category_id"] as $filter) {
    if ($_GET[$filter] ?? "") {
        $where .= " AND c.$filter=?";
        $args[] = $_GET[$filter];
    }
}

$q = $pdo->prepare(
    "SELECT c.*,d.department_name,cc.category_name,st.student_number,su.full_name student_name,(SELECT u2.full_name FROM complaint_assignments ca JOIN staff s2 ON s2.staff_id=ca.staff_id JOIN users u2 ON u2.user_id=s2.user_id WHERE ca.complaint_id=c.complaint_id AND ca.unassigned_at IS NULL ORDER BY ca.assignment_id DESC LIMIT 1) assigned_staff,(SELECT ca.staff_id FROM complaint_assignments ca WHERE ca.complaint_id=c.complaint_id AND ca.unassigned_at IS NULL ORDER BY ca.assignment_id DESC LIMIT 1) assigned_staff_id FROM complaints c JOIN departments d ON d.department_id=c.department_id JOIN complaint_categories cc ON cc.category_id=c.category_id JOIN students st ON st.student_id=c.student_id JOIN users su ON su.user_id=st.user_id WHERE $where ORDER BY c.submitted_at DESC",
);
$q->execute($args);
$rows = $q->fetchAll();
$deps = $pdo->query("SELECT * FROM departments ORDER BY department_name")->fetchAll();
$cats = $pdo->query("SELECT * FROM complaint_categories ORDER BY category_name")->fetchAll();
$staffByDepartment = [];
$staffRows = $pdo->query(
    "SELECT s.staff_id,s.department_id,s.designation,u.full_name,d.department_name FROM staff s JOIN users u ON u.user_id=s.user_id LEFT JOIN departments d ON d.department_id=s.department_id WHERE s.status='ACTIVE' AND u.is_active=1 ORDER BY u.full_name",
)->fetchAll();
foreach ($staffRows as $staff) {
    $staffByDepartment[] = $staff;
}

require "../includes/header.php";
?>
<div class="container admin-complaints">
    <div class="page-heading">
        <div>
            <p class="eyebrow">SERVICE DESK / ADMIN</p>
            <h1>Complaint Queue</h1>
            <p class="muted">Review new requests and assign them to any active staff member.</p>
        </div>
    </div>

    <section class="card complaint-filters">
        <h2>Find complaints</h2>
        <form method="get">
            <input name="q" placeholder="Ticket, title, student ID, name" value="<?= e($_GET["q"] ?? "") ?>">
            <div class="filter-grid">
                <select name="status">
                    <option value="">All statuses</option>
                    <?php foreach (["SUBMITTED", "UNDER_REVIEW", "ASSIGNED", "IN_PROGRESS", "RESOLVED", "CLOSED"] as $status): ?>
                        <option value="<?= e($status) ?>" <?= $status === ($_GET["status"] ?? "") ? "selected" : "" ?>><?= e(str_replace("_", " ", $status)) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="priority">
                    <option value="">All priorities</option>
                    <?php foreach (["Low", "Medium", "High", "Critical"] as $priority): ?>
                        <option value="<?= e($priority) ?>" <?= $priority === ($_GET["priority"] ?? "") ? "selected" : "" ?>><?= e($priority) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="department_id">
                    <option value="">All departments</option>
                    <?php foreach ($deps as $department): ?>
                        <option value="<?= $department["department_id"] ?>" <?= (string) $department["department_id"] === ($_GET["department_id"] ?? "") ? "selected" : "" ?>><?= e($department["department_name"]) ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="category_id">
                    <option value="">All categories</option>
                    <?php foreach ($cats as $category): ?>
                        <option value="<?= $category["category_id"] ?>" <?= (string) $category["category_id"] === ($_GET["category_id"] ?? "") ? "selected" : "" ?>><?= e($category["category_name"]) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button class="btn">Apply filters</button>
            <a class="btn secondary" href="complaints.php">Clear</a>
        </form>
    </section>

    <section class="card complaint-table">
        <div class="table-scroll">
            <table>
                <thead>
                    <tr><th>Ticket</th><th>Student</th><th>Complaint</th><th>Department</th><th>Priority</th><th>Status</th><th>Assignment</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><a href="/scsms_V1/student/complaint.php?id=<?= $row["complaint_id"] ?>"><?= e($row["ticket_number"]) ?></a><span class="muted ticket-category"><?= e($row["category_name"]) ?></span></td>
                            <td><?= e($row["student_number"] . " — " . $row["student_name"]) ?></td>
                            <td><strong><?= e($row["title"]) ?></strong><span class="muted ticket-category"><?= e($row["submitted_at"]) ?></span></td>
                            <td><?= e($row["department_name"]) ?></td>
                            <td><?= e($row["priority"]) ?></td>
                            <td><?= badge($row["status"]) ?></td>
                            <td>
                                <?php if (in_array($row["status"], ["SUBMITTED", "UNDER_REVIEW", "ASSIGNED", "IN_PROGRESS"], true)): ?>
                                    <form class="assignment-form" method="post">
                                        <input type="hidden" name="csrf" value="<?= csrf() ?>">
                                        <input type="hidden" name="complaint_id" value="<?= $row["complaint_id"] ?>">
                                        <label class="sr-only" for="staff-<?= $row["complaint_id"] ?>">Assign <?= e($row["ticket_number"]) ?> to staff</label>
                                        <select id="staff-<?= $row["complaint_id"] ?>" name="staff_id" required>
                                            <option value="">Choose staff</option>
                                            <?php foreach ($staffByDepartment as $staff): ?>
                                                <option value="<?= $staff["staff_id"] ?>" <?= (string) ($row["assigned_staff_id"] ?? "") === (string) $staff["staff_id"] ? "selected" : "" ?>><?= e($staff["full_name"] . ($staff["designation"] ? " · " . $staff["designation"] : "") . ($staff["department_name"] ? " · " . $staff["department_name"] : "")) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input class="assignment-remarks" name="remarks" maxlength="500" placeholder="Assignment note (optional)" aria-label="Assignment note for <?= e($row["ticket_number"]) ?>">
                                        <button class="btn assign-button" <?= !$staffByDepartment ? "disabled" : "" ?>><?= $row["assigned_staff"] ? "Reassign" : "Assign" ?></button>
                                    </form>
                                    <?php if (!$staffByDepartment): ?><span class="muted">No active staff accounts. Create one from Users first.</span><?php endif; ?>
                                <?php else: ?>
                                    <span class="assigned-name"><?= e($row["assigned_staff"] ?? "Not assigned") ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?>
                        <tr><td class="empty-state" colspan="7">No complaints match these filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require "../includes/footer.php"; ?>
