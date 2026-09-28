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
    try {
        $pdo->beginTransaction();
        $pdo->prepare(
            "INSERT INTO users(email,password_hash,full_name,phone) VALUES(?,?,?,?)",
        )->execute([
            trim($_POST["email"]),
            password_hash($_POST["password"], PASSWORD_DEFAULT),
            trim($_POST["full_name"]),
            trim($_POST["phone"]),
        ]);
        $uid = $pdo->lastInsertId();
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
) ?></option><?php endforeach; ?></select><label>Program (student)</label><input name="program"><label>Trimester (student)</label><input name="trimester"><label>Designation (staff/head)</label><input name="designation"><button class="btn">Create</button></form></div><div class="card"><h2>Users</h2><table><tr><th>Name</th><th>Email</th><th>Role</th><th>Active</th></tr><?php foreach (
    $list
    as $u
): ?><tr><td><?= e($u["full_name"]) ?></td><td><?= e(
    $u["email"],
) ?></td><td><?= e($u["roles"]) ?></td><td><?= e(
    $u["is_active"] ? "Yes" : "No",
) ?></td></tr><?php endforeach; ?></table></div></div><?php require "../includes/footer.php"; ?>
