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

$q = $pdo->prepare(
    "SELECT COUNT(*) total,COALESCE(SUM(c.status IN ('SUBMITTED','UNDER_REVIEW','ASSIGNED','IN_PROGRESS')),0) active,COALESCE(SUM(c.status IN ('RESOLVED','CLOSED')),0) completed,COALESCE(SUM(c.status='IGNORED'),0) ignored,COALESCE(SUM(c.status='SUBMITTED'),0) new_requests,COALESCE(SUM(c.status='IN_PROGRESS'),0) in_progress,COALESCE(SUM(c.status IN ('SUBMITTED','UNDER_REVIEW','ASSIGNED','IN_PROGRESS') AND NOT EXISTS (SELECT 1 FROM complaint_assignments ca WHERE ca.complaint_id=c.complaint_id AND ca.unassigned_at IS NULL)),0) unassigned FROM complaints c WHERE c.department_id=?",
);
$q->execute([$departmentId]);
$stats = $q->fetch();

$q = $pdo->prepare(
    "SELECT c.complaint_id,c.ticket_number,c.title,c.status,c.priority,c.submitted_at,st.student_number,su.full_name student_name,(SELECT u.full_name FROM complaint_assignments ca JOIN staff s ON s.staff_id=ca.staff_id JOIN users u ON u.user_id=s.user_id WHERE ca.complaint_id=c.complaint_id AND ca.unassigned_at IS NULL ORDER BY ca.assignment_id DESC LIMIT 1) assigned_staff FROM complaints c JOIN students st ON st.student_id=c.student_id JOIN users su ON su.user_id=st.user_id WHERE c.department_id=? ORDER BY c.submitted_at DESC LIMIT 8",
);
$q->execute([$departmentId]);
$recent = $q->fetchAll();
$departmentChartValues = [
    (int) $stats["unassigned"],
    max(0, (int) $stats["active"] - (int) $stats["unassigned"]),
    (int) $stats["completed"],
    0,
    (int) $stats["ignored"],
];
$departmentChartTotal = array_sum($departmentChartValues);
$departmentChartStops = [0, 0, 0, 0];
if ($departmentChartTotal > 0) {
    $departmentChartStops = [
        round($departmentChartValues[0] / $departmentChartTotal * 100, 2),
        round(array_sum(array_slice($departmentChartValues, 0, 2)) / $departmentChartTotal * 100, 2),
        round(array_sum(array_slice($departmentChartValues, 0, 3)) / $departmentChartTotal * 100, 2),
        round(array_sum(array_slice($departmentChartValues, 0, 4)) / $departmentChartTotal * 100, 2),
    ];
}

require "../includes/header.php";
?>
<div class="dashboard-page dashboard-department">
    <div class="dashboard-heading">
        <div class="dashboard-heading-copy">
            <p class="dashboard-kicker">Department services / overview</p>
            <h1><?= e($department["department_name"]) ?> Dashboard</h1>
            <p class="dashboard-description">Department-wide complaint activity and staff workload.</p>
        </div>
        <div class="dashboard-heading-actions"><a class="btn" href="complaints.php">Review complaints</a><a class="btn secondary" href="performance.php">Staff performance</a></div>
    </div>

    <section class="dashboard-metrics" aria-label="Department complaint totals">
        <div class="dashboard-metric"><span class="dashboard-metric-label">Total</span><strong class="dashboard-metric-value"><?= (int) $stats["total"] ?></strong><span class="dashboard-metric-note">All department complaints</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Open</span><strong class="dashboard-metric-value"><?= (int) $stats["active"] ?></strong><span class="dashboard-metric-note">Requiring action</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">New requests</span><strong class="dashboard-metric-value"><?= (int) $stats["new_requests"] ?></strong><span class="dashboard-metric-note">Awaiting first review</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">In progress</span><strong class="dashboard-metric-value"><?= (int) $stats["in_progress"] ?></strong><span class="dashboard-metric-note">Assigned and underway</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Unassigned</span><strong class="dashboard-metric-value"><?= (int) $stats["unassigned"] ?></strong><span class="dashboard-metric-note">Needs a staff owner</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Resolved / closed</span><strong class="dashboard-metric-value"><?= (int) $stats["completed"] ?></strong><span class="dashboard-metric-note">Completed requests</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Ignored</span><strong class="dashboard-metric-value"><?= (int) $stats["ignored"] ?></strong><span class="dashboard-metric-note">Closed after review</span></div>
    </section>

    <section class="dashboard-panel">
        <div class="dashboard-panel-heading"><div><h2>Workload distribution</h2><p>Open cases split by assignment, alongside completed cases</p></div></div>
        <div class="dashboard-chart-layout">
            <div class="dashboard-donut <?= $departmentChartTotal === 0 ? "is-empty" : "" ?>" role="img" aria-label="<?= $departmentChartTotal ?> department complaints by assignment and completion" style="--chart-stop-one:<?= $departmentChartStops[0] ?>%;--chart-stop-two:<?= $departmentChartStops[1] ?>%;--chart-stop-three:<?= $departmentChartStops[2] ?>%;--chart-stop-four:<?= $departmentChartStops[3] ?>%">
                <div class="dashboard-donut-center"><strong><?= (int) $stats["total"] ?></strong><span>Total complaints</span></div>
            </div>
            <div class="dashboard-chart-legend" aria-label="Department workload breakdown">
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-pending)"></span><span>Unassigned</span><strong><?= $departmentChartValues[0] ?></strong></div>
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-progress)"></span><span>Open and assigned</span><strong><?= $departmentChartValues[1] ?></strong></div>
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-resolved)"></span><span>Resolved / closed</span><strong><?= $departmentChartValues[2] ?></strong></div>
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-ignored)"></span><span>Ignored</span><strong><?= $departmentChartValues[4] ?></strong></div>
            </div>
        </div>
    </section>

    <section class="dashboard-panel">
        <div class="dashboard-panel-heading"><div><h2>Recent complaints</h2><p>Latest activity routed to this department</p></div><a class="btn secondary" href="complaints.php">View all</a></div>
        <div class="dashboard-panel-body flush dashboard-table-wrap">
            <table class="dashboard-table">
                <thead><tr><th>Ticket</th><th>Student</th><th>Complaint</th><th>Priority</th><th>Status</th><th>Assigned staff</th><th>Action</th></tr></thead>
                <tbody>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td><a href="/scsms_V1/student/complaint.php?id=<?= (int) $row["complaint_id"] ?>"><?= e($row["ticket_number"]) ?></a></td>
                            <td><?= e($row["student_number"] . " - " . $row["student_name"]) ?></td>
                            <td><?= e($row["title"]) ?></td>
                            <td><?= e($row["priority"]) ?></td>
                            <td><?= badge($row["status"]) ?></td>
                            <td><?= e($row["assigned_staff"] ?? "Unassigned") ?></td>
                            <td><?php if (in_array($row["status"], ["SUBMITTED", "UNDER_REVIEW", "ASSIGNED", "IN_PROGRESS"], true)): ?><a class="btn" href="assign.php?id=<?= (int) $row["complaint_id"] ?>"><?= $row["assigned_staff"] ? "Reassign" : "Assign" ?></a><?php elseif ($row["status"] === "RESOLVED"): ?><a class="btn secondary" href="close.php?id=<?= (int) $row["complaint_id"] ?>">Close</a><?php else: ?><span class="muted">No action</span><?php endif; ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recent): ?><tr><td class="dashboard-empty" colspan="7"><strong>No complaints yet</strong>Requests routed to this department will appear here.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require "../includes/footer.php"; ?>
