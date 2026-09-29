<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Staff");
$q = $pdo->prepare("SELECT staff_id FROM staff WHERE user_id=? AND status='ACTIVE'");
$q->execute([$_SESSION["user_id"]]);
$sid = $q->fetchColumn();
$rows = [];
if ($sid) {
    $q = $pdo->prepare(
        "SELECT c.*,d.department_name,st.student_number,u.full_name student_name FROM complaints c JOIN complaint_assignments ca ON ca.complaint_id=c.complaint_id AND ca.unassigned_at IS NULL JOIN staff s ON s.staff_id=ca.staff_id JOIN students st ON st.student_id=c.student_id JOIN users u ON u.user_id=st.user_id LEFT JOIN departments d ON d.department_id=c.department_id WHERE s.staff_id=? ORDER BY c.updated_at DESC",
    );
    $q->execute([$sid]);
    $rows = $q->fetchAll();
}
$workload = ["active" => 0, "pending" => 0, "in_progress" => 0, "resolved" => 0, "closed" => 0];
foreach ($rows as $row) {
    if (in_array($row["status"], ["SUBMITTED", "UNDER_REVIEW", "ASSIGNED", "IN_PROGRESS"], true)) {
        $workload["active"]++;
    }
    if (in_array($row["status"], ["SUBMITTED", "UNDER_REVIEW", "ASSIGNED"], true)) {
        $workload["pending"]++;
    } elseif ($row["status"] === "IN_PROGRESS") {
        $workload["in_progress"]++;
    } elseif ($row["status"] === "RESOLVED") {
        $workload["resolved"]++;
    } elseif ($row["status"] === "CLOSED") {
        $workload["closed"]++;
    }
}
$staffChartTotal = array_sum([$workload["pending"], $workload["in_progress"], $workload["resolved"], $workload["closed"]]);
$staffStatusValues = [
    "Pending" => $workload["pending"],
    "In progress" => $workload["in_progress"],
    "Resolved" => $workload["resolved"],
    "Closed" => $workload["closed"],
];
$maxStaffStatus = max(1, ...array_values($staffStatusValues));
$staffChartStops = [0, 0, 0];
if ($staffChartTotal > 0) {
    $staffChartStops = [
        round($workload["pending"] / $staffChartTotal * 100, 2),
        round(($workload["pending"] + $workload["in_progress"]) / $staffChartTotal * 100, 2),
        round(($workload["pending"] + $workload["in_progress"] + $workload["resolved"]) / $staffChartTotal * 100, 2),
    ];
}
require "../includes/header.php";
?>
<div class="dashboard-page dashboard-staff">
    <div class="dashboard-heading">
        <div class="dashboard-heading-copy">
            <p class="dashboard-kicker">Service desk / staff workspace</p>
            <h1>Assigned workload</h1>
            <p class="dashboard-description">Review your active cases and keep each request moving.</p>
        </div>
    </div>

    <?php if ($sid): ?>
        <section class="dashboard-metrics" aria-label="Workload totals">
            <div class="dashboard-metric"><span class="dashboard-metric-label">Active workload</span><strong class="dashboard-metric-value"><?= $workload["active"] ?></strong><span class="dashboard-metric-note">Cases needing action</span></div>
            <div class="dashboard-metric"><span class="dashboard-metric-label">In progress</span><strong class="dashboard-metric-value"><?= $workload["in_progress"] ?></strong><span class="dashboard-metric-note">Currently being processed</span></div>
            <div class="dashboard-metric"><span class="dashboard-metric-label">Resolved</span><strong class="dashboard-metric-value"><?= $workload["resolved"] ?></strong><span class="dashboard-metric-note">Resolution submitted</span></div>
            <div class="dashboard-metric"><span class="dashboard-metric-label">Closed</span><strong class="dashboard-metric-value"><?= $workload["closed"] ?></strong><span class="dashboard-metric-note">Completed cases</span></div>
        </section>
        <section class="dashboard-panel">
            <div class="dashboard-panel-heading"><div><h2>Assigned case mix</h2><p>Current distribution of cases in your queue</p></div></div>
            <div class="dashboard-chart-layout">
                <div class="dashboard-donut <?= $staffChartTotal === 0 ? "is-empty" : "" ?>" role="img" aria-label="<?= $staffChartTotal ?> assigned cases distributed by status" style="--chart-stop-one:<?= $staffChartStops[0] ?>%;--chart-stop-two:<?= $staffChartStops[1] ?>%;--chart-stop-three:<?= $staffChartStops[2] ?>%">
                    <div class="dashboard-donut-center"><strong><?= $staffChartTotal ?></strong><span>Assigned cases</span></div>
                </div>
                <div class="dashboard-chart-legend" aria-label="Assigned case count by status">
                    <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-pending)"></span><span>Pending</span><strong><?= $workload["pending"] ?></strong></div>
                    <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-progress)"></span><span>In progress</span><strong><?= $workload["in_progress"] ?></strong></div>
                    <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-resolved)"></span><span>Resolved</span><strong><?= $workload["resolved"] ?></strong></div>
                    <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-closed)"></span><span>Closed</span><strong><?= $workload["closed"] ?></strong></div>
                </div>
            </div>
        </section>
        <section class="dashboard-panel">
            <div class="dashboard-panel-heading"><div><h2>Cases by status</h2><p>Compare your assigned and completed work</p></div></div>
            <div class="dashboard-panel-body dashboard-status-bars" aria-label="Case volume by status">
                <?php foreach ($staffStatusValues as $label => $count): ?>
                    <div class="dashboard-status-bar-row">
                        <span><?= e($label) ?></span>
                        <span class="dashboard-status-track" role="img" aria-label="<?= (int) $count ?> <?= e(strtolower($label)) ?> cases">
                            <span class="dashboard-status-fill status-<?= e(strtolower(str_replace(" ", "-", $label))) ?>" style="width: <?= (int) round($count / $maxStaffStatus * 100) ?>%"></span>
                        </span>
                        <strong><?= (int) $count ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>

    <section class="dashboard-panel">
        <div class="dashboard-panel-heading">
            <div><h2>Complaint queue</h2><p>Cases assigned to your account</p></div>
        </div>
        <?php if (!$sid): ?>
            <div class="dashboard-empty"><strong>No active staff profile</strong>Your staff profile is inactive or unavailable. Contact an administrator.</div>
        <?php elseif (!$rows): ?>
            <div class="dashboard-empty"><strong>Your queue is clear</strong>New assignments will appear here.</div>
        <?php else: ?>
            <div class="dashboard-panel-body flush dashboard-table-wrap">
                <table class="dashboard-table">
                    <thead><tr><th>Ticket</th><th>Student</th><th>Complaint</th><th>Priority</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><a href="/scsms_V1/student/complaint.php?id=<?= (int) $r["complaint_id"] ?>"><?= e($r["ticket_number"]) ?></a></td>
                                <td><?= e($r["student_number"] . " - " . $r["student_name"]) ?></td>
                                <td><?= e($r["title"]) ?><span class="muted"><?= e($r["department_name"] ?? "") ?></span></td>
                                <td><?= e($r["priority"]) ?></td>
                                <td><?= badge($r["status"]) ?></td>
                                <td><?php if (in_array($r["status"], ["SUBMITTED", "UNDER_REVIEW", "ASSIGNED", "IN_PROGRESS"], true)): ?><a class="btn" href="update.php?id=<?= (int) $r["complaint_id"] ?>">Update status</a><?php else: ?><a class="btn secondary" href="/scsms_V1/student/complaint.php?id=<?= (int) $r["complaint_id"] ?>">View details</a><?php endif; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php require "../includes/footer.php"; ?>
