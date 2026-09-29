<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Student");
$q = $pdo->prepare(
    'SELECT COUNT(*) total,SUM(status IN (\'SUBMITTED\',\'UNDER_REVIEW\',\'ASSIGNED\')) pending,SUM(status=\'IN_PROGRESS\') progress,SUM(status=\'RESOLVED\') resolved,SUM(status=\'CLOSED\') closed,SUM(status=\'IGNORED\') ignored FROM complaints WHERE student_id=(SELECT student_id FROM students WHERE user_id=?)',
);
$q->execute([$_SESSION["user_id"]]);
$s = $q->fetch();
$q = $pdo->prepare(
    "SELECT c.*,d.department_name FROM complaints c JOIN students st ON st.student_id=c.student_id LEFT JOIN departments d ON d.department_id=c.department_id WHERE st.user_id=? ORDER BY c.submitted_at DESC LIMIT 5",
);
$q->execute([$_SESSION["user_id"]]);
$rows = $q->fetchAll();
$studentChartValues = [
    (int) ($s["pending"] ?? 0),
    (int) ($s["progress"] ?? 0),
    (int) ($s["resolved"] ?? 0),
    (int) ($s["closed"] ?? 0),
    (int) ($s["ignored"] ?? 0),
];
$studentChartTotal = array_sum($studentChartValues);
$studentChartStops = [0, 0, 0, 0];
if ($studentChartTotal > 0) {
    $studentChartStops = [
        round($studentChartValues[0] / $studentChartTotal * 100, 2),
        round(array_sum(array_slice($studentChartValues, 0, 2)) / $studentChartTotal * 100, 2),
        round(array_sum(array_slice($studentChartValues, 0, 3)) / $studentChartTotal * 100, 2),
        round(array_sum(array_slice($studentChartValues, 0, 4)) / $studentChartTotal * 100, 2),
    ];
}
require "../includes/header.php";
?>
<div class="dashboard-page dashboard-student">
    <div class="dashboard-heading">
        <div class="dashboard-heading-copy">
            <p class="dashboard-kicker">Student services / overview</p>
            <h1>Student Dashboard</h1>
            <p class="dashboard-description">Track your requests and review the latest updates.</p>
        </div>
        <div class="dashboard-heading-actions">
            <a class="btn" href="create_complaint.php">Submit New Complaint</a>
        </div>
    </div>

    <section class="dashboard-metrics" aria-label="Complaint totals">
        <div class="dashboard-metric"><span class="dashboard-metric-label">Total</span><strong class="dashboard-metric-value"><?= (int) ($s["total"] ?? 0) ?></strong><span class="dashboard-metric-note">All submitted tickets</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Pending</span><strong class="dashboard-metric-value"><?= (int) ($s["pending"] ?? 0) ?></strong><span class="dashboard-metric-note">Awaiting action</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">In progress</span><strong class="dashboard-metric-value"><?= (int) ($s["progress"] ?? 0) ?></strong><span class="dashboard-metric-note">Being worked on</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Resolved</span><strong class="dashboard-metric-value"><?= (int) ($s["resolved"] ?? 0) ?></strong><span class="dashboard-metric-note">Resolution provided</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Closed</span><strong class="dashboard-metric-value"><?= (int) ($s["closed"] ?? 0) ?></strong><span class="dashboard-metric-note">Completed tickets</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Ignored</span><strong class="dashboard-metric-value"><?= (int) ($s["ignored"] ?? 0) ?></strong><span class="dashboard-metric-note">Closed after review</span></div>
    </section>

    <section class="dashboard-panel">
        <div class="dashboard-panel-heading"><div><h2>Status distribution</h2><p>How your submitted tickets are progressing</p></div></div>
        <div class="dashboard-chart-layout">
            <div class="dashboard-donut <?= $studentChartTotal === 0 ? "is-empty" : "" ?>" role="img" aria-label="<?= $studentChartTotal ?> total complaints distributed by status" style="--chart-stop-one:<?= $studentChartStops[0] ?>%;--chart-stop-two:<?= $studentChartStops[1] ?>%;--chart-stop-three:<?= $studentChartStops[2] ?>%;--chart-stop-four:<?= $studentChartStops[3] ?>%">
                <div class="dashboard-donut-center"><strong><?= (int) ($s["total"] ?? 0) ?></strong><span>Total tickets</span></div>
            </div>
            <div class="dashboard-chart-legend" aria-label="Complaint count by status">
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-pending)"></span><span>Pending</span><strong><?= $studentChartValues[0] ?></strong></div>
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-progress)"></span><span>In progress</span><strong><?= $studentChartValues[1] ?></strong></div>
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-resolved)"></span><span>Resolved</span><strong><?= $studentChartValues[2] ?></strong></div>
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-closed)"></span><span>Closed</span><strong><?= $studentChartValues[3] ?></strong></div>
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-ignored)"></span><span>Ignored</span><strong><?= $studentChartValues[4] ?></strong></div>
            </div>
        </div>
    </section>

    <section class="dashboard-panel">
        <div class="dashboard-panel-heading">
            <div><h2>Recent tickets</h2><p>Your five most recent complaints</p></div>
            <a class="btn secondary" href="complaints.php">View all complaints</a>
        </div>
        <div class="dashboard-panel-body flush dashboard-table-wrap">
            <table class="dashboard-table">
                <thead><tr><th>Ticket</th><th>Title</th><th>Department</th><th>Status</th><th>Priority</th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><a href="complaint.php?id=<?= (int) $r["complaint_id"] ?>"><?= e($r["ticket_number"]) ?></a></td>
                            <td><?= e($r["title"]) ?></td>
                            <td><?= e($r["department_name"] ?? "Not assigned") ?></td>
                            <td><?= badge($r["status"]) ?></td>
                            <td><?= e($r["priority"]) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?>
                        <tr><td class="dashboard-empty" colspan="5"><strong>No complaints yet</strong>Your submitted complaints will appear here.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require "../includes/footer.php"; ?>
