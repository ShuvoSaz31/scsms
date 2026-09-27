<?php require_once __DIR__ . "/../includes/functions.php"; require_role("Department Head");
$id = (int) $_GET["id"];
$q = $pdo->prepare(
    "SELECT c.*,d.department_id FROM complaints c JOIN departments d ON d.department_id=c.department_id JOIN staff h ON h.department_id=d.department_id WHERE c.complaint_id=? AND h.user_id=?",
);
$q->execute([$id, $_SESSION["user_id"]]);
$c = $q->fetch();
if (!$c) {
    exit("Access denied.");
}
$q = $pdo->prepare(
    'SELECT s.staff_id,u.full_name,s.designation FROM staff s JOIN users u ON u.user_id=s.user_id WHERE s.department_id=? AND s.status=\'ACTIVE\' ORDER BY u.full_name',
);
$q->execute([$c["department_id"]]);
$staff = $q->fetchAll();
$err = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf();
    try {
        $pdo->beginTransaction();
        $pdo->prepare(
            "UPDATE complaint_assignments SET unassigned_at=NOW() WHERE complaint_id=? AND unassigned_at IS NULL",
        )->execute([$id]);
        $pdo->prepare(
            "INSERT INTO complaint_assignments(complaint_id,staff_id,assigned_by,remarks) VALUES(?,?,?,?)",
        )->execute([
            $id,
            $_POST["staff_id"],
            $_SESSION["user_id"],
            trim($_POST["remarks"]),
        ]);
        if ($c["status"] !== "ASSIGNED") {
            if (!transition_ok($c["status"], "ASSIGNED")) {
                throw new Exception(
                    "Complaint cannot be assigned from current status.",
                );
            }
            $pdo->prepare(
                'UPDATE complaints SET status=\'ASSIGNED\' WHERE complaint_id=?',
            )->execute([$id]);
            add_history(
                $pdo,
                $id,
                $c["status"],
                "ASSIGNED",
                $_SESSION["user_id"],
                "Staff assigned",
            );
        }
        $q = $pdo->prepare(
            "SELECT user_id,full_name FROM users WHERE user_id=(SELECT user_id FROM staff WHERE staff_id=?)",
        );
        $q->execute([$_POST["staff_id"]]);
        $u = $q->fetch();
        notify(
            $pdo,
            $u["user_id"],
            $id,
            "Complaint assigned",
            "Complaint " . $c["ticket_number"] . " has been assigned to you.",
        );
        $pdo->commit();
        flash("success", "Staff assigned successfully.");
        header("Location: dashboard.php");
        exit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $err = $e->getMessage();
    }
}
require "../includes/header.php";
?><div class="container"><div class="card"><h1>Assign Complaint</h1><?php if (
    $err
): ?><div class="alert error"><?= e(
    $err,
) ?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><label>Staff</label><select name="staff_id" required><?php foreach (
    $staff
    as $s
): ?><option value="<?= $s["staff_id"] ?>"><?= e(
    $s["full_name"] . " — " . $s["designation"],
) ?></option><?php endforeach; ?></select><label>Remarks</label><textarea name="remarks"></textarea><button class="btn">Assign</button></form></div></div><?php require "../includes/footer.php"; ?>
