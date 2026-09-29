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
    "SELECT COUNT(*) total,COALESCE(SUM(c.status IN ('SUBMITTED','UNDER_REVIEW','ASSIGNED','IN_PROGRESS')),0) active,COALESCE(SUM(c.status IN ('RESOLVED','CLOSED')),0) completed,COALESCE(SUM(c.status='IGNORED'),0) ignored,COALESCE(SUM(c.status IN ('SUBMITTED','UNDER_REVIEW','ASSIGNED','IN_PROGRESS') AND NOT EXISTS (SELECT 1 FROM complaint_assignments ca WHERE ca.complaint_id=c.complaint_id AND ca.unassigned_at IS NULL)),0) unassigned,ROUND(AVG(CASE WHEN c.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR,c.submitted_at,c.resolved_at) END),1) average_resolution_hours FROM complaints c WHERE c.department_id=?",
);
$q->execute([$departmentId]);
$stats = $q->fetch();

$q = $pdo->prepare(
    "SELECT s.staff_id,u.full_name,s.designation,(SELECT COUNT(*) FROM complaint_assignments ca JOIN complaints c ON c.complaint_id=ca.complaint_id WHERE ca.staff_id=s.staff_id AND ca.unassigned_at IS NULL AND c.status IN ('SUBMITTED','UNDER_REVIEW','ASSIGNED','IN_PROGRESS') AND c.department_id=?) active_count,(SELECT COUNT(*) FROM complaints c WHERE c.resolved_by=u.user_id AND c.department_id=?) resolved_count,(SELECT ROUND(AVG(TIMESTAMPDIFF(HOUR,c.submitted_at,c.resolved_at)),1) FROM complaints c WHERE c.resolved_by=u.user_id AND c.department_id=? AND c.resolved_at IS NOT NULL) average_resolution_hours FROM staff s JOIN users u ON u.user_id=s.user_id WHERE s.department_id=? AND s.status='ACTIVE' AND u.is_active=1 ORDER BY active_count DESC,u.full_name",
);
$q->execute([$departmentId, $departmentId, $departmentId, $departmentId]);
$staffRows = $q->fetchAll();
$maxActiveAssignments = 1;
$maxResolvedAssignments = 1;
foreach ($staffRows as $row) {
    $maxActiveAssignments = max($maxActiveAssignments, (int) $row["active_count"]);
    $maxResolvedAssignments = max($maxResolvedAssignments, (int) $row["resolved_count"]);
}
$performanceChartTotal = (int) $stats["active"] + (int) $stats["completed"] + (int) $stats["ignored"];
$openWorkPercent = $performanceChartTotal > 0 ? round((int) $stats["active"] / $performanceChartTotal * 100, 2) : 0;
$completedWorkPercent = $performanceChartTotal > 0 ? round(((int) $stats["active"] + (int) $stats["completed"]) / $performanceChartTotal * 100, 2) : 0;

require "../includes/header.php";
?>
<div class="dashboard-page dashboard-performance">
    <div class="dashboard-heading">
        <div class="dashboard-heading-copy">
            <p class="dashboard-kicker">Department services / analytics</p>
            <h1><?= e($department["department_name"]) ?> Performance</h1>
            <p class="dashboard-description">Current workload and resolution outcomes for your department.</p>
        </div>
    </div>

    <section class="dashboard-metrics" aria-label="Department performance totals">
        <div class="dashboard-metric"><span class="dashboard-metric-label">Total complaints</span><strong class="dashboard-metric-value"><?= (int) $stats["total"] ?></strong><span class="dashboard-metric-note">Department-wide</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Open workload</span><strong class="dashboard-metric-value"><?= (int) $stats["active"] ?></strong><span class="dashboard-metric-note">Needs action</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Resolved / closed</span><strong class="dashboard-metric-value"><?= (int) $stats["completed"] ?></strong><span class="dashboard-metric-note">Completed cases</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Ignored</span><strong class="dashboard-metric-value"><?= (int) $stats["ignored"] ?></strong><span class="dashboard-metric-note">Closed after review</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Unassigned</span><strong class="dashboard-metric-value"><?= (int) $stats["unassigned"] ?></strong><span class="dashboard-metric-note">Included in open workload</span></div>
        <div class="dashboard-metric"><span class="dashboard-metric-label">Avg. resolution time</span><strong class="dashboard-metric-value"><?= $stats["average_resolution_hours"] === null ? "-" : e($stats["average_resolution_hours"] . "h") ?></strong><span class="dashboard-metric-note">From submission to resolution</span></div>
    </section>

    <section class="dashboard-panel">
        <div class="dashboard-panel-heading"><div><h2>Open and completed</h2><p>Share of department complaints by work state</p></div></div>
        <div class="dashboard-chart-layout">
            <div class="dashboard-donut <?= $performanceChartTotal === 0 ? "is-empty" : "" ?>" role="img" aria-label="<?= $performanceChartTotal ?> complaints: <?= (int) $stats["active"] ?> open, <?= (int) $stats["completed"] ?> completed, and <?= (int) $stats["ignored"] ?> ignored" style="--chart-progress:var(--chart-resolved);--chart-resolved:var(--chart-ignored);--chart-stop-one:<?= $openWorkPercent ?>%;--chart-stop-two:<?= $completedWorkPercent ?>%;--chart-stop-three:100%;--chart-stop-four:100%">
                <div class="dashboard-donut-center"><strong><?= $performanceChartTotal ?></strong><span>Complaints</span></div>
            </div>
            <div class="dashboard-chart-legend" aria-label="Department completion breakdown">
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-pending)"></span><span>Open workload</span><strong><?= (int) $stats["active"] ?></strong></div>
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-resolved)"></span><span>Resolved / closed</span><strong><?= (int) $stats["completed"] ?></strong></div>
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:var(--chart-ignored)"></span><span>Ignored</span><strong><?= (int) $stats["ignored"] ?></strong></div>
                <div class="dashboard-chart-legend-item"><span class="dashboard-chart-swatch" style="--swatch-color:#6c757d"></span><span>Unassigned (within open)</span><strong><?= (int) $stats["unassigned"] ?></strong></div>
            </div>
        </div>
    </section>

    <section class="dashboard-panel">
        <div class="dashboard-panel-heading"><div><h2>Staff performance</h2><p>Current assignments and resolved cases by staff member</p></div></div>
        <div class="dashboard-panel-body flush dashboard-table-wrap">
            <table class="dashboard-table">
                <thead><tr><th>Staff member</th><th>Current assignments</th><th>Resolved complaints</th><th>Average resolution time</th></tr></thead>
                <tbody>
                    <?php foreach ($staffRows as $row): ?>
                        <tr>
                            <td><?= e($row["full_name"] . ($row["designation"] ? " - " . $row["designation"] : "")) ?></td>
                            <td><div class="dashboard-performance-bar"><strong><?= (int) $row["active_count"] ?></strong><span class="dashboard-performance-track" role="img" aria-label="<?= (int) $row["active_count"] ?> current assignments"><span class="dashboard-performance-fill" style="width: <?= (int) round((int) $row["active_count"] / $maxActiveAssignments * 100) ?>%"></span></span></div></td>
                            <td><div class="dashboard-performance-bar"><strong><?= (int) $row["resolved_count"] ?></strong><span class="dashboard-performance-track" role="img" aria-label="<?= (int) $row["resolved_count"] ?> resolved complaints"><span class="dashboard-performance-fill is-completed" style="width: <?= (int) round((int) $row["resolved_count"] / $maxResolvedAssignments * 100) ?>%"></span></span></div></td>
                            <td><?= $row["average_resolution_hours"] === null ? "-" : e($row["average_resolution_hours"] . " hrs") ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$staffRows): ?><tr><td class="empty-state" colspan="4">No active staff members are registered in this department.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
<?php require "../includes/footer.php"; ?>
