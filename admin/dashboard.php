<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Admin");
$stats = $pdo
    ->query(
        "SELECT COUNT(*) total,SUM(status IN ('SUBMITTED','UNDER_REVIEW','ASSIGNED')) pending,SUM(status='IN_PROGRESS') progress,SUM(status='RESOLVED') resolved,SUM(status='CLOSED') closed,SUM(status='IGNORED') ignored FROM complaints",
    )
    ->fetch();
$bycat = $pdo
    ->query(
        "SELECT cc.category_name,COUNT(c.complaint_id) total FROM complaint_categories cc LEFT JOIN complaints c ON c.category_id=cc.category_id GROUP BY cc.category_id ORDER BY total DESC",
    )
    ->fetchAll();
$bydep = $pdo
    ->query(
        "SELECT d.department_name,COUNT(c.complaint_id) total FROM departments d LEFT JOIN complaints c ON c.department_id=d.department_id GROUP BY d.department_id ORDER BY total DESC",
    )
    ->fetchAll();
$completed = (int) ($stats["resolved"] ?? 0) + (int) ($stats["closed"] ?? 0);
$completionRate = (int) ($stats["total"] ?? 0) > 0 ? round($completed / (int) $stats["total"] * 100) : 0;
$maxCategory = 1;
foreach ($bycat as $row) {
    $maxCategory = max($maxCategory, (int) $row["total"]);
}
$maxDepartment = 1;
foreach ($bydep as $row) {
    $maxDepartment = max($maxDepartment, (int) $row["total"]);
}
$adminChartValues = [
    (int) ($stats["pending"] ?? 0),
    (int) ($stats["progress"] ?? 0),
    (int) ($stats["resolved"] ?? 0),
    (int) ($stats["closed"] ?? 0),
    (int) ($stats["ignored"] ?? 0),
];
$adminChartTotal = array_sum($adminChartValues);
$adminChartStops = [0, 0, 0, 0];
if ($adminChartTotal > 0) {
    $adminChartStops = [
        round($adminChartValues[0] / $adminChartTotal * 100, 2),
        round(array_sum(array_slice($adminChartValues, 0, 2)) / $adminChartTotal * 100, 2),
        round(array_sum(array_slice($adminChartValues, 0, 3)) / $adminChartTotal * 100, 2),
        round(array_sum(array_slice($adminChartValues, 0, 4)) / $adminChartTotal * 100, 2),
    ];
}
require "../includes/header.php";
?>
<div class="dashboard-page dashboard-admin">
    <div class="dashboard-heading">
        <div class="dashboard-heading-copy">
            <p class="dashboard-kicker">System operations / overview</p>
            <h1>Admin Dashboard</h1>
            <p class="dashboard-description">A system-wide view of complaint volume and resolution.</p>
        </div>
        <div class="dashboard-heading-actions"><a class="btn" href="complaints.php">Open complaint queue</a></div>
    </div>

    <section class="dashboard-metrics" aria-label="System health counters">
        <div class="dashboard-metric"><span class="dashboard-metric-label">Total complaints</span><strong class="dashboard-metric-value"><?= (int) ($stats["total"] ?? 0) ?></strong><span class="dashboard-metric-note">Across all departments</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Pending</span><strong class="dashboard-metric-value"><?= (int) ($stats["pending"] ?? 0) ?></strong><span class="dashboard-metric-note">Submitted, review, or assigned</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">In progress</span><strong class="dashboard-metric-value"><?= (int) ($stats["progress"] ?? 0) ?></strong><span class="dashboard-metric-note">Being handled by staff</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Resolved</span><strong class="dashboard-metric-value"><?= (int) ($stats["resolved"] ?? 0) ?></strong><span class="dashboard-metric-note">Awaiting closure or feedback</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Closed</span><strong class="dashboard-metric-value"><?= (int) ($stats["closed"] ?? 0) ?></strong><span class="dashboard-metric-note">Completed complaints</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Ignored</span><strong class="dashboard-metric-value"><?= (int) ($stats["ignored"] ?? 0) ?></strong><span class="dashboard-metric-note">Closed after review</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Completion rate</span><strong class="dashboard-metric-value"><?= $completionRate ?>%</strong><span class="dashboard-metric-note">Resolved and closed</span></div>
    </section>

    <section class="dashboard-panel">
        <div class="dashboard-panel-heading"><div><h2>System status mix</h2><p>Global complaint volume by current status</p></div></div>
        <div class="dashboard-chart-layout">
            <div class="dashboard-donut <?= $adminChartTotal === 0 ? "is-empty" : "" ?>" role="img" aria-label="<?= $adminChartTotal ?> system complaints distributed by status" style="--chart-stop-one:<?= $adminChartStops[0] ?>%;--chart-stop-two:<?= $adminChartStops[1] ?>%;--chart-stop-three:<?= $adminChartStops[2] ?>%;--chart-stop-four:<?= $adminChartStops[3] ?>%">
                <div class="dashboard-donut-center"><strong><?= (int) ($stats["total"] ?? 0) ?></strong><span>Total complaints</span></div>
            </div>
            <div class="dashboard-chart-legend" aria-label="System complaint count by status">
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-pending)"></span><span>Pending</span><strong><?= $adminChartValues[0] ?></strong></div>
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-progress)"></span><span>In progress</span><strong><?= $adminChartValues[1] ?></strong></div>
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-resolved)"></span><span>Resolved</span><strong><?= $adminChartValues[2] ?></strong></div>
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-closed)"></span><span>Closed</span><strong><?= $adminChartValues[3] ?></strong></div>
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-ignored)"></span><span>Ignored</span><strong><?= $adminChartValues[4] ?></strong></div>
            </div>
        </div>
    </section>

    <div class="dashboard-split">
        <section class="dashboard-panel">
            <div class="dashboard-panel-heading"><div><h2>By category</h2><p>Complaint distribution across service types</p></div></div>
            <div class="dashboard-panel-body flush dashboard-table-wrap">
                <table class="dashboard-table dashboard-ranking-table">
                    <thead><tr><th>Category</th><th>Complaints</th></tr></thead>
                    <tbody>
                        <?php foreach ($bycat as $r): ?>
                            <tr><td><?= e($r["category_name"]) ?></td><td><?= (int) $r["total"] ?><span class="dashboard-bar-track"><span class="dashboard-bar-fill" style="width: <?= (int) round((int) $r["total"] / $maxCategory * 100) ?>%"></span></span></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$bycat): ?><tr><td class="dashboard-empty" colspan="2"><strong>No categories</strong>Create categories to classify complaints.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
        <section class="dashboard-panel">
            <div class="dashboard-panel-heading"><div><h2>By department</h2><p>Complaint volume by destination</p></div></div>
            <div class="dashboard-panel-body flush dashboard-table-wrap">
                <table class="dashboard-table dashboard-ranking-table">
                    <thead><tr><th>Department</th><th>Complaints</th></tr></thead>
                    <tbody>
                        <?php foreach ($bydep as $r): ?>
                            <tr><td><?= e($r["department_name"]) ?></td><td><?= (int) $r["total"] ?><span class="dashboard-bar-track"><span class="dashboard-bar-fill" style="width: <?= (int) round((int) $r["total"] / $maxDepartment * 100) ?>%"></span></span></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$bydep): ?><tr><td class="dashboard-empty" colspan="2"><strong>No departments</strong>Create departments to route complaints.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>
<?php require "../includes/footer.php"; ?>
