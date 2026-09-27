<?php require_once __DIR__ . "/includes/functions.php"; require_login();
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf();
    $pdo->prepare(
        "UPDATE notifications SET is_read=1 WHERE user_id=?",
    )->execute([$_SESSION["user_id"]]);
}
$q = $pdo->prepare(
    "SELECT * FROM notifications WHERE user_id=? ORDER BY created_at DESC",
);
$q->execute([$_SESSION["user_id"]]);
$rows = $q->fetchAll();
require "includes/header.php";
?><div class="container"><div class="card"><h1>Notifications</h1><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><button class="btn secondary">Mark All Read</button></form><?php foreach (
    $rows
    as $n
): ?><div class="card"><b><?= e($n["title"]) ?></b><p><?= e(
    $n["message"],
) ?></p><small><?= e(
    $n["created_at"],
) ?></small></div><?php endforeach; ?></div></div><?php require "includes/footer.php"; ?>
