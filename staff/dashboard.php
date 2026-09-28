<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Staff");
$q = $pdo->prepare("SELECT staff_id FROM staff WHERE user_id=? AND status='ACTIVE'");
$q->execute([$_SESSION["user_id"]]);
$sid = $q->fetchColumn();
$rows = [];
if ($sid) {
    $q = $pdo->prepare(
        "SELECT c.*,d.department_name,st.student_number,u.full_name student_name FROM complaints c JOIN complaint_assignments ca ON ca.complaint_id=c.complaint_id AND ca.unassigned_at IS NULL JOIN staff s ON s.staff_id=ca.staff_id JOIN students st ON st.student_id=c.student_id JOIN users u ON u.user_id=st.user_id JOIN departments d ON d.department_id=c.department_id WHERE s.staff_id=? ORDER BY c.updated_at DESC",
    );
    $q->execute([$sid]);
    $rows = $q->fetchAll();
}
require "../includes/header.php";
?>
<div class="container">
    <h1>Staff Dashboard</h1>
    <div class="card">
        <?php if (!$sid): ?>
            <p class="empty-state">Your account does not have an active staff profile. Ask an administrator to check your staff account.</p>
        <?php elseif (!$rows): ?>
            <p class="empty-state">No complaints are assigned to you yet. Once an administrator assigns a complaint to your account, it will appear here.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr><th>Ticket</th><th>Student</th><th>Title</th><th>Priority</th><th>Status</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><a href="/scsms_V1/student/complaint.php?id=<?= $r["complaint_id"] ?>"><?= e($r["ticket_number"]) ?></a></td>
                            <td><?= e($r["student_number"]) ?></td>
                            <td><?= e($r["title"]) ?></td>
                            <td><?= e($r["priority"]) ?></td>
                            <td><?= badge($r["status"]) ?></td>
                            <td><a class="btn" href="update.php?id=<?= $r["complaint_id"] ?>">Process</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php require "../includes/footer.php"; ?>
