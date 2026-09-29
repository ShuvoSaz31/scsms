<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Admin");
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf();
    if (($_POST["action"] ?? "") === "delete") {
        $departmentId = (int) ($_POST["department_id"] ?? 0);
        $q = $pdo->prepare("DELETE FROM departments WHERE department_id=?");
        $q->execute([$departmentId]);
        flash(
            $q->rowCount() ? "success" : "error",
            $q->rowCount()
                ? "Department deleted. Related records have been preserved."
                : "Department not found.",
        );
    } else {
        $pdo->prepare(
            "INSERT INTO departments(department_name,description) VALUES(?,?)",
        )->execute([trim($_POST["name"]), trim($_POST["description"])]);
        flash("success", "Department added.");
    }
    header("Location: departments.php");
    exit();
}
$rows = $pdo
    ->query("SELECT * FROM departments ORDER BY department_name")
    ->fetchAll();
require "../includes/header.php";
?><div class="container"><div class="card"><h1>Departments</h1><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><label>Name</label><input name="name" required><label>Description</label><input name="description"><button class="btn">Add</button></form></div><div class="card"><table><tr><th>Name</th><th>Description</th><th>Active</th><th>Actions</th></tr><?php foreach (
    $rows
    as $r
): ?><tr><td><?= e($r["department_name"]) ?></td><td><?= e(
    $r["description"],
) ?></td><td><?= e(
    $r["is_active"] ? "Yes" : "No",
) ?></td><td><form method="post" onsubmit="return confirm('Delete this department? Related complaints and user profiles will be kept without a department link.')"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="department_id" value="<?= (int) $r["department_id"] ?>"><button class="btn secondary">Delete</button></form></td></tr><?php endforeach; ?></table></div></div><?php require "../includes/footer.php"; ?>
