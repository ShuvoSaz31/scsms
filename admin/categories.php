<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Admin");
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf();
    if ($_POST["kind"] === "cat") {
        $pdo->prepare(
            "INSERT INTO complaint_categories(category_name,description) VALUES(?,?)",
        )->execute([trim($_POST["name"]), trim($_POST["description"])]);
    } else {
        $pdo->prepare(
            "INSERT INTO complaint_subcategories(category_id,subcategory_name) VALUES(?,?)",
        )->execute([$_POST["category_id"], trim($_POST["name"])]);
    }
    flash("success", "Category data added.");
    header("Location: categories.php");
    exit();
}
$cats = $pdo
    ->query("SELECT * FROM complaint_categories ORDER BY category_name")
    ->fetchAll();
$subs = $pdo
    ->query(
        "SELECT s.*,c.category_name FROM complaint_subcategories s JOIN complaint_categories c ON c.category_id=s.category_id ORDER BY c.category_name,s.subcategory_name",
    )
    ->fetchAll();
require "../includes/header.php";
?><div class="container"><div class="two"><div class="card"><h1>Add Category</h1><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="kind" value="cat"><label>Name</label><input name="name" required><label>Description</label><input name="description"><button class="btn">Add</button></form></div><div class="card"><h1>Add Subcategory</h1><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="kind" value="sub"><label>Category</label><select name="category_id"><?php foreach (
    $cats
    as $c
): ?><option value="<?= $c["category_id"] ?>"><?= e(
    $c["category_name"],
) ?></option><?php endforeach; ?></select><label>Name</label><input name="name" required><button class="btn">Add</button></form></div></div><div class="card"><h2>Categories</h2><?php foreach (
    $cats
    as $c
): ?><p><b><?= e($c["category_name"]) ?></b> — <?= e(
    $c["description"],
) ?></p><?php endforeach; ?><h2>Subcategories</h2><?php foreach (
    $subs
    as $s
): ?><p><?= e($s["category_name"]) ?> → <?= e(
     $s["subcategory_name"],
 ) ?></p><?php endforeach; ?></div></div><?php require "../includes/footer.php"; ?>
