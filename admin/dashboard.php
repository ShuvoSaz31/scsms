<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Admin");
$stats = $pdo
    ->query(
        "SELECT COUNT(*) total,SUM(status IN ('SUBMITTED','UNDER_REVIEW','ASSIGNED')) pending,SUM(status='IN_PROGRESS') progress,SUM(status='RESOLVED') resolved,SUM(status='CLOSED') closed FROM complaints",
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
require "../includes/header.php";
?><div class="container"><h1>Admin Dashboard</h1><div class="grid"><div class="stat">Total<b><?= $stats[
    "total"
] ?></b></div><div class="stat">Pending<b><?= $stats[
    "pending"
] ?></b></div><div class="stat">In Progress<b><?= $stats[
    "progress"
] ?></b></div><div class="stat">Resolved<b><?= $stats[
    "resolved"
] ?></b></div><div class="stat">Closed<b><?= $stats[
    "closed"
] ?></b></div></div><div class="two"><div class="card"><h2>By Category</h2><table><tr><th>Category</th><th>Total</th></tr><?php foreach (
    $bycat
    as $r
): ?><tr><td><?= e($r["category_name"]) ?></td><td><?= $r[
    "total"
] ?></td></tr><?php endforeach; ?></table></div><div class="card"><h2>By Department</h2><table><tr><th>Department</th><th>Total</th></tr><?php foreach (
    $bydep
    as $r
): ?><tr><td><?= e($r["department_name"]) ?></td><td><?= $r[
    "total"
] ?></td></tr><?php endforeach; ?></table></div></div></div><?php require "../includes/footer.php"; ?>
