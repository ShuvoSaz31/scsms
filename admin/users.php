<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Admin");
$roles = $pdo->query("SELECT * FROM roles ORDER BY role_id")->fetchAll();
$deps = $pdo
    ->query(
        "SELECT * FROM departments WHERE is_active=1 ORDER BY department_name",
    )
    ->fetchAll();
$err = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf();
    if (($_POST["action"] ?? "") === "toggle_active") {
        $userId = (int) ($_POST["user_id"] ?? 0);
        $pdo->prepare(
            "UPDATE users SET is_active=1-is_active WHERE user_id=?",
        )->execute([$userId]);
        flash("success", "User active status updated.");
        header("Location: users.php");
        exit();
    }
    try {
        $email = strtolower(trim($_POST["email"] ?? ""));
        $phone = trim($_POST["phone"] ?? "");
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Enter a valid email address.");
        }
        if (!preg_match('/^[0-9+().\-\s]{5,30}$/D', $phone) || preg_match_all('/[0-9]/', $phone) < 5) {
            throw new Exception("Enter a valid phone number with at least 5 digits.");
        }
        $pdo->beginTransaction();
        $pdo->prepare(
            "INSERT INTO users(email,password_hash,full_name,phone) VALUES(?,?,?,?)",
        )->execute([
            $email,
            password_hash($_POST["password"], PASSWORD_DEFAULT),
            trim($_POST["full_name"]),
            $phone,
        ]);
        $uid = $pdo->lastInsertId();
        $pdo->prepare(
            "INSERT INTO user_emails(user_id,email,is_primary) VALUES(?,?,1)",
        )->execute([$uid, $email]);
        $pdo->prepare(
            "INSERT INTO user_phones(user_id,phone,is_primary) VALUES(?,?,1)",
        )->execute([$uid, $phone]);
        $rid = (int) $_POST["role_id"];
        $pdo->prepare("INSERT INTO user_roles VALUES(?,?)")->execute([
            $uid,
            $rid,
        ]);
        $rn = $pdo->prepare("SELECT role_name FROM roles WHERE role_id=?");
        $rn->execute([$rid]);
        $role = $rn->fetchColumn();
        if ($role === "Student") {
            $pdo->prepare(
                "INSERT INTO students(user_id,student_number,department_id,program,trimester) VALUES(?,?,?,?,?)",
            )->execute([
                $uid,
                $_POST["number"],
                $_POST["department_id"],
                $_POST["program"],
                $_POST["trimester"],
            ]);
        } elseif (in_array($role, ["Staff", "Department Head"])) {
            $pdo->prepare(
                "INSERT INTO staff(user_id,staff_number,department_id,designation) VALUES(?,?,?,?)",
            )->execute([
                $uid,
                $_POST["number"],
                $_POST["department_id"],
                $_POST["designation"],
            ]);
        }
        $pdo->commit();
        flash("success", "User created.");
        header("Location: users.php");
        exit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $err = "Could not create user. Email or ID may already exist.";
    }
}
$list = $pdo
    ->query(
        'SELECT u.*,GROUP_CONCAT(r.role_name SEPARATOR ", ") roles FROM users u JOIN user_roles ur ON ur.user_id=u.user_id JOIN roles r ON r.role_id=ur.role_id GROUP BY u.user_id ORDER BY u.user_id DESC',
    )
    ->fetchAll();
require "../includes/header.php";
?><div class="container"><div class="card"><h1>Create User</h1><?php if (
    $err
): ?><div class="alert error"><?= e(
    $err,
) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><label>Full name</label><input name="full_name" required><label>Email</label><input type="email" name="email" required><label>Password</label><input type="password" name="password" required minlength="6"><label>Phone</label><input name="phone"><label>Role</label><select name="role_id" required><?php foreach (
    $roles
    as $r
): ?><option value="<?= $r["role_id"] ?>"><?= e(
    $r["role_name"],
) ?></option><?php endforeach; ?></select><label>Student/Staff Number</label><input name="number" required><label>Department</label><select name="department_id" required><?php foreach (
    $deps
    as $d
): ?><option value="<?= $d["department_id"] ?>"><?= e(
    $d["department_name"],
) ?></option><?php endforeach; ?></select><label>Program (student)</label><input name="program"><label>Trimester (student)</label><input name="trimester"><label>Designation (staff/head)</label><input name="designation"><button class="btn">Create</button></form></div><div class="card"><h2>Users</h2><table><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th><th>Actions</th></tr><?php foreach (
    $list
    as $u
): ?><tr><td><?= e($u["full_name"]) ?></td><td><?= e(
    $u["email"],
) ?></td><td><?= e($u["roles"]) ?></td><td><?= e(
    $u["is_active"] ? "Active" : "Deactivated",
) ?></td><td><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="toggle_active"><input type="hidden" name="user_id" value="<?= (int) $u["user_id"] ?>"><button class="btn secondary"><?= $u["is_active"] ? "Deactivate" : "Activate" ?></button></form></td></tr><?php endforeach; ?></table></div></div><?php require "../includes/footer.php"; ?>
