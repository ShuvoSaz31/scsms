<?php require_once "includes/functions.php";
require_login();
$q = $pdo->prepare(
    "SELECT a.*,c.student_id,st.user_id student_user,ca.staff_id FROM attachments a JOIN complaints c ON c.complaint_id=a.complaint_id JOIN students st ON st.student_id=c.student_id LEFT JOIN complaint_assignments ca ON ca.complaint_id=c.complaint_id AND ca.unassigned_at IS NULL WHERE a.attachment_id=?",
);
$q->execute([(int) $_GET["id"]]);
$a = $q->fetch();
if (!$a) {
    exit("Not found.");
}
$ok =
    $_SESSION["role"] === "Admin" || $a["student_user"] == $_SESSION["user_id"];
if (!$ok && $_SESSION["role"] === "Staff") {
    $q = $pdo->prepare(
        "SELECT 1 FROM complaint_assignments ca JOIN staff s ON s.staff_id=ca.staff_id WHERE ca.complaint_id=? AND s.user_id=?",
    );
    $q->execute([$a["complaint_id"], $_SESSION["user_id"]]);
    $ok = (bool) $q->fetchColumn();
}
if (!$ok && $_SESSION["role"] === "Department Head") {
    $ok = true;
}
if (!$ok) {
    exit("Access denied.");
}
$path = __DIR__ . "/" . $a["file_path"];
if (!is_file($path)) {
    exit("File missing.");
}
header("Content-Type: " . $a["mime_type"]);
header("Content-Length: " . filesize($path));
header(
    'Content-Disposition: attachment; filename="' .
        basename($a["original_name"]) .
        '"',
);
readfile($path);
