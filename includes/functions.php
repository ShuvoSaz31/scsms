<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . "/../config/database.php";
function e($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, "UTF-8");
}
function require_login()
{
    if (empty($_SESSION["user_id"])) {
        header("Location: /scsms_V1/index.php");
        exit();
    }
}
function require_role($roles)
{
    require_login();
    $roles = (array) $roles;
    if (!in_array($_SESSION["role"], $roles, true)) {
        http_response_code(403);
        exit("Access denied.");
    }
}
function csrf()
{
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}
function check_csrf()
{
    if (!hash_equals($_SESSION["csrf"] ?? "", $_POST["csrf"] ?? "")) {
        http_response_code(419);
        exit("Invalid request.");
    }
}
function flash($type, $msg)
{
    $_SESSION["flash"] = [$type, $msg];
}
function show_flash()
{
    if (!empty($_SESSION["flash"])) {
        [$t, $m] = $_SESSION["flash"];
        unset($_SESSION["flash"]);
        echo '<div class="alert ' . e($t) . '">' . e($m) . "</div>";
    }
}
function home()
{
    return match ($_SESSION["role"] ?? "") {
        "Student" => "/scsms_V1/student/dashboard.php",
        "Staff" => "/scsms_V1/staff/dashboard.php",
        "Department Head" => "/scsms_V1/department/dashboard.php",
        "Admin" => "/scsms_V1/admin/dashboard.php",
        default => "/scsms_V1/index.php",
    };
}
function go_home()
{
    header("Location: " . home());
    exit();
}
function badge($s)
{
    return '<span class="badge ' .
        strtolower(e($s)) .
        '">' .
        e(str_replace("_", " ", $s)) .
        "</span>";
}
function transition_ok($old, $new)
{
    $map = [
        "SUBMITTED" => ["UNDER_REVIEW", "ASSIGNED"],
        "UNDER_REVIEW" => ["ASSIGNED"],
        "ASSIGNED" => ["IN_PROGRESS"],
        "IN_PROGRESS" => ["RESOLVED"],
        "RESOLVED" => ["CLOSED"],
    ];
    return in_array($new, $map[$old] ?? [], true);
}
function add_history($pdo, $cid, $old, $new, $uid, $remarks = "")
{
    $q = $pdo->prepare(
        "INSERT INTO complaint_status_history(complaint_id,old_status,new_status,changed_by,remarks) VALUES(?,?,?,?,?)",
    );
    $q->execute([$cid, $old ?: null, $new, $uid, $remarks]);
}
function notify($pdo, $uid, $cid, $title, $msg)
{
    $q = $pdo->prepare(
        "INSERT INTO notifications(user_id,complaint_id,title,message) VALUES(?,?,?,?)",
    );
    $q->execute([$uid, $cid, $title, $msg]);
}
function current_assignment($pdo, $cid)
{
    $q = $pdo->prepare(
        "SELECT ca.*,s.staff_id,u.full_name,u.email FROM complaint_assignments ca JOIN staff s ON s.staff_id=ca.staff_id JOIN users u ON u.user_id=s.user_id WHERE ca.complaint_id=? AND ca.unassigned_at IS NULL ORDER BY ca.assignment_id DESC LIMIT 1",
    );
    $q->execute([$cid]);
    return $q->fetch();
}
function upload_file($pdo, $cid, $uid, $file)
{
    if (!$file || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("Upload failed.");
    }
    if ($file["size"] > 5 * 1024 * 1024) {
        throw new Exception("File must be 5 MB or smaller.");
    }
    $allowed = [
        "pdf" => "application/pdf",
        "png" => "image/png",
        "jpg" => "image/jpeg",
        "jpeg" => "image/jpeg",
        "doc" => "application/msword",
        "docx" =>
            "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
    ];
    $ext = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        throw new Exception("Invalid file type.");
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file["tmp_name"]);
    if ($mime !== $allowed[$ext]) {
        throw new Exception("Invalid file content.");
    }
    $name = bin2hex(random_bytes(16)) . "." . $ext;
    $dir = __DIR__ . "/../uploads";
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    if (!move_uploaded_file($file["tmp_name"], $dir . "/" . $name)) {
        throw new Exception("Could not save file.");
    }
    $q = $pdo->prepare(
        "INSERT INTO attachments(complaint_id,uploaded_by,original_name,stored_name,file_path,mime_type,file_size) VALUES(?,?,?,?,?,?,?)",
    );
    $q->execute([
        $cid,
        $uid,
        $file["name"],
        $name,
        "uploads/" . $name,
        $mime,
        $file["size"],
    ]);
    return $pdo->lastInsertId();
}
?>
