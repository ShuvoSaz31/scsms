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
?>
<div class="container admin-taxonomy">
    <div class="page-heading">
        <div>
            <p class="dashboard-kicker">Service desk / configuration</p>
            <h1>Categories</h1>
            <p class="dashboard-description">Organize complaint requests into clear service areas.</p>
        </div>
    </div>
    <div class="two admin-taxonomy-forms">
        <section class="card admin-taxonomy-card">
            <p class="taxonomy-eyebrow">Top-level classification</p>
            <h2>Add category</h2>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <input type="hidden" name="kind" value="cat">
                <label for="category-name">Category name</label>
                <input id="category-name" name="name" required maxlength="100">
                <label for="category-description">Description</label>
                <input id="category-description" name="description" maxlength="255">
                <button class="btn">Add category</button>
            </form>
        </section>
        <section class="card admin-taxonomy-card">
            <p class="taxonomy-eyebrow">Nested classification</p>
            <h2>Add subcategory</h2>
            <form method="post">
                <input type="hidden" name="csrf" value="<?= csrf() ?>">
                <input type="hidden" name="kind" value="sub">
                <label for="subcategory-category">Parent category</label>
                <select id="subcategory-category" name="category_id" required>
                    <option value="">Choose a category</option>
                    <?php foreach ($cats as $category): ?>
                        <option value="<?= (int) $category["category_id"] ?>"><?= e($category["category_name"]) ?></option>
                    <?php endforeach; ?>
                </select>
                <label for="subcategory-name">Subcategory name</label>
                <input id="subcategory-name" name="name" required maxlength="100">
                <button class="btn">Add subcategory</button>
            </form>
        </section>
    </div>
    <section class="card taxonomy-list-card">
        <div class="taxonomy-list-grid">
            <div>
                <h2>Categories</h2>
                <?php foreach ($cats as $category): ?>
                    <div class="taxonomy-list-row"><strong><?= e($category["category_name"]) ?></strong><span><?= e($category["description"] ?: "No description") ?></span></div>
                <?php endforeach; ?>
            </div>
            <div>
                <h2>Subcategories</h2>
                <?php foreach ($subs as $subcategory): ?>
                    <div class="taxonomy-list-row"><strong><?= e($subcategory["subcategory_name"]) ?></strong><span><?= e($subcategory["category_name"]) ?></span></div>
                <?php endforeach; ?>
                <?php if (!$subs): ?><p class="muted">No subcategories added yet.</p><?php endif; ?>
            </div>
        </div>
    </section>
</div>
<?php require "../includes/footer.php"; ?>
