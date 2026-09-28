<?php require_once __DIR__ . "/includes/functions.php"; require_login();
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf();
    $pdo->prepare(
        "UPDATE users SET full_name=?,phone=? WHERE user_id=?",
    )->execute([
        trim($_POST["full_name"]),
        trim($_POST["phone"]),
        $_SESSION["user_id"],
    ]);
    $_SESSION["name"] = trim($_POST["full_name"]);
    flash("success", "Profile updated.");
    header("Location: profile.php");
    exit();
}
$q = $pdo->prepare("SELECT * FROM users WHERE user_id=?");
$q->execute([$_SESSION["user_id"]]);
$u = $q->fetch();
require "includes/header.php";
?><div class="container"><div class="card"><h1>Profile</h1><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><label>Name</label><input name="full_name" value="<?= e(
    $u["full_name"],
) ?>" required><label>Email</label><input value="<?= e(
    $u["email"],
) ?>" disabled><label>Phone</label><input name="phone" value="<?= e(
    $u["phone"],
) ?>"><button class="btn">Save</button></form></div></div><?php require "includes/footer.php"; ?>
