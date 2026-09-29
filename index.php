<?php require_once "includes/functions.php";
if (!empty($_SESSION["user_id"])) {
    go_home();
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf();
    $q = $pdo->prepare(
        "SELECT u.*,r.role_name FROM users u JOIN user_roles ur ON ur.user_id=u.user_id JOIN roles r ON r.role_id=ur.role_id WHERE u.email=? OR EXISTS (SELECT 1 FROM user_emails ue WHERE ue.user_id=u.user_id AND ue.email=?) LIMIT 1",
    );
    $email = strtolower(trim($_POST["email"] ?? ""));
    $q->execute([$email, $email]);
    $u = $q->fetch();
    if ($u && password_verify($_POST["password"] ?? "", $u["password_hash"])) {
        session_regenerate_id(true);
        $_SESSION["user_id"] = $u["user_id"];
        $_SESSION["role"] = $u["role_name"];
        $_SESSION["name"] = $u["full_name"];
        go_home();
    } else {
        $err = "Invalid email or password.";
    }
}
require "includes/header.php";
?><div class="login card"><h1>SCSMS Login</h1><?php if (
    isset($err)
): ?><div class="alert error"><?= e(
    $err,
) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><label>Email</label><input type="email" name="email" required><label>Password</label><input type="password" name="password" required><button class="btn">Login</button></form><p class="muted">Demo password: password</p></div><?php require "includes/footer.php"; ?>
