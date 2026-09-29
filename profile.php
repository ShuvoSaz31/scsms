<?php
require_once __DIR__ . "/includes/functions.php";
require_login();
$isEditing = !empty($profileEditMode) || basename($_SERVER["SCRIPT_NAME"] ?? "") === "profile_edit.php";

function profile_email_in_use($pdo, $email, $userId, $excludeEmailId = 0)
{
    $q = $pdo->prepare("SELECT user_id FROM users WHERE email=? AND user_id<>? LIMIT 1");
    $q->execute([$email, $userId]);
    if ($q->fetchColumn()) {
        return true;
    }
    $q = $pdo->prepare("SELECT email_id FROM user_emails WHERE email=? AND email_id<>? LIMIT 1");
    $q->execute([$email, $excludeEmailId]);
    return (bool) $q->fetchColumn();
}

function profile_valid_phone($phone)
{
    return preg_match('/^[0-9+().\-\s]{5,30}$/D', $phone) && preg_match_all('/[0-9]/', $phone) >= 5;
}

$userId = (int) $_SESSION["user_id"];
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf();
    $action = $_POST["action"] ?? "";

    try {
        if ($action === "update_profile") {
            $name = trim($_POST["full_name"] ?? "");
            if ($name === "") {
                throw new Exception("Name is required.");
            }
            $pdo->prepare("UPDATE users SET full_name=? WHERE user_id=?")->execute([$name, $userId]);
            $_SESSION["name"] = $name;
            flash("success", "Profile updated.");
        } elseif ($action === "update_avatar") {
            $file = $_FILES["profile_photo"] ?? null;
            if (!$file || $file["error"] === UPLOAD_ERR_NO_FILE) {
                throw new Exception("Choose a photo to upload.");
            }
            if ($file["error"] !== UPLOAD_ERR_OK || $file["size"] > 2 * 1024 * 1024) {
                throw new Exception("Photo upload failed or exceeds the 2 MB limit.");
            }
            $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file["tmp_name"]);
            $image = getimagesize($file["tmp_name"]);
            $extensions = ["image/jpeg" => "jpg", "image/png" => "png", "image/webp" => "webp"];
            if (!$image || !isset($extensions[$mime]) || $image["mime"] !== $mime) {
                throw new Exception("Upload a valid JPG, PNG, or WebP image.");
            }
            if ($image[0] > 6000 || $image[1] > 6000) {
                throw new Exception("Photo dimensions must be 6000 by 6000 pixels or smaller.");
            }
            $avatarDirectory = __DIR__ . "/uploads/avatars";
            if (!is_dir($avatarDirectory) && !mkdir($avatarDirectory, 0755, true) && !is_dir($avatarDirectory)) {
                throw new Exception("Could not create the profile photo directory.");
            }
            $fileName = bin2hex(random_bytes(16)) . "." . $extensions[$mime];
            $relativePath = "uploads/avatars/" . $fileName;
            if (!move_uploaded_file($file["tmp_name"], $avatarDirectory . "/" . $fileName)) {
                throw new Exception("Could not save the profile photo.");
            }
            $q = $pdo->prepare("SELECT profile_photo FROM users WHERE user_id=?");
            $q->execute([$userId]);
            $oldPhoto = $q->fetchColumn();
            try {
                $pdo->prepare("UPDATE users SET profile_photo=? WHERE user_id=?")->execute([$relativePath, $userId]);
            } catch (Throwable $e) {
                unlink($avatarDirectory . "/" . $fileName);
                throw $e;
            }
            if ($oldPhoto && str_starts_with($oldPhoto, "uploads/avatars/")) {
                $oldFile = __DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $oldPhoto);
                if (is_file($oldFile)) {
                    unlink($oldFile);
                }
            }
            flash("success", "Profile photo updated.");
        } elseif ($action === "change_password") {
            $q = $pdo->prepare("SELECT password_hash FROM users WHERE user_id=?");
            $q->execute([$userId]);
            $passwordHash = $q->fetchColumn();
            $currentPassword = $_POST["current_password"] ?? "";
            $newPassword = $_POST["new_password"] ?? "";
            if (!$passwordHash || !password_verify($currentPassword, $passwordHash)) {
                throw new Exception("Current password is incorrect.");
            }
            if (strlen($newPassword) < 8) {
                throw new Exception("New password must be at least 8 characters.");
            }
            if ($newPassword !== ($_POST["confirm_password"] ?? "")) {
                throw new Exception("New password and confirmation do not match.");
            }
            $pdo->prepare("UPDATE users SET password_hash=? WHERE user_id=?")->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
            flash("success", "Password updated.");
        } elseif ($action === "add_email") {
            $email = strtolower(trim($_POST["email"] ?? ""));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Enter a valid email address.");
            }
            if (profile_email_in_use($pdo, $email, $userId)) {
                throw new Exception("That email address is already in use.");
            }
            $pdo->beginTransaction();
            $q = $pdo->prepare("SELECT COUNT(*) FROM user_emails WHERE user_id=?");
            $q->execute([$userId]);
            $isPrimary = (int) $q->fetchColumn() === 0;
            $pdo->prepare("INSERT INTO user_emails(user_id,email,is_primary) VALUES(?,?,?)")->execute([$userId, $email, (int) $isPrimary]);
            if ($isPrimary) {
                $pdo->prepare("UPDATE users SET email=? WHERE user_id=?")->execute([$email, $userId]);
            }
            $pdo->commit();
            flash("success", "Email address added.");
        } elseif ($action === "update_email") {
            $emailId = (int) ($_POST["email_id"] ?? 0);
            $email = strtolower(trim($_POST["email"] ?? ""));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Enter a valid email address.");
            }
            $pdo->beginTransaction();
            $q = $pdo->prepare("SELECT email_id,is_primary FROM user_emails WHERE email_id=? AND user_id=? FOR UPDATE");
            $q->execute([$emailId, $userId]);
            $contact = $q->fetch();
            if (!$contact) {
                throw new Exception("Email address not found.");
            }
            if (profile_email_in_use($pdo, $email, $userId, $emailId)) {
                throw new Exception("That email address is already in use.");
            }
            $pdo->prepare("UPDATE user_emails SET email=? WHERE email_id=? AND user_id=?")->execute([$email, $emailId, $userId]);
            if ($contact["is_primary"]) {
                $pdo->prepare("UPDATE users SET email=? WHERE user_id=?")->execute([$email, $userId]);
            }
            $pdo->commit();
            flash("success", "Email address updated.");
        } elseif ($action === "make_primary_email") {
            $emailId = (int) ($_POST["email_id"] ?? 0);
            $pdo->beginTransaction();
            $q = $pdo->prepare("SELECT email FROM user_emails WHERE email_id=? AND user_id=? FOR UPDATE");
            $q->execute([$emailId, $userId]);
            $email = $q->fetchColumn();
            if (!$email) {
                throw new Exception("Email address not found.");
            }
            $pdo->prepare("UPDATE user_emails SET is_primary=0 WHERE user_id=?")->execute([$userId]);
            $pdo->prepare("UPDATE user_emails SET is_primary=1 WHERE email_id=? AND user_id=?")->execute([$emailId, $userId]);
            $pdo->prepare("UPDATE users SET email=? WHERE user_id=?")->execute([$email, $userId]);
            $pdo->commit();
            flash("success", "Primary email updated. You can sign in with it now.");
        } elseif ($action === "delete_email") {
            $emailId = (int) ($_POST["email_id"] ?? 0);
            $pdo->beginTransaction();
            $q = $pdo->prepare("SELECT email_id,email,is_primary FROM user_emails WHERE user_id=? ORDER BY email_id FOR UPDATE");
            $q->execute([$userId]);
            $emails = $q->fetchAll();
            $target = null;
            foreach ($emails as $contact) {
                if ((int) $contact["email_id"] === $emailId) {
                    $target = $contact;
                    break;
                }
            }
            if (!$target) {
                throw new Exception("Email address not found.");
            }
            if (count($emails) <= 1) {
                throw new Exception("At least one email address must remain on your profile.");
            }
            $pdo->prepare("DELETE FROM user_emails WHERE email_id=? AND user_id=?")->execute([$emailId, $userId]);
            if ($target["is_primary"]) {
                $replacement = null;
                foreach ($emails as $contact) {
                    if ((int) $contact["email_id"] !== $emailId) {
                        $replacement = $contact;
                        break;
                    }
                }
                $pdo->prepare("UPDATE user_emails SET is_primary=1 WHERE email_id=?")->execute([$replacement["email_id"]]);
                $pdo->prepare("UPDATE users SET email=? WHERE user_id=?")->execute([$replacement["email"], $userId]);
            }
            $pdo->commit();
            flash("success", "Email address deleted.");
        } elseif ($action === "add_phone") {
            $phone = trim($_POST["phone"] ?? "");
            if (!profile_valid_phone($phone)) {
                throw new Exception("Enter a valid phone number with at least 5 digits.");
            }
            $pdo->beginTransaction();
            $q = $pdo->prepare("SELECT COUNT(*) FROM user_phones WHERE user_id=?");
            $q->execute([$userId]);
            $isPrimary = (int) $q->fetchColumn() === 0;
            $pdo->prepare("INSERT INTO user_phones(user_id,phone,is_primary) VALUES(?,?,?)")->execute([$userId, $phone, (int) $isPrimary]);
            if ($isPrimary) {
                $pdo->prepare("UPDATE users SET phone=? WHERE user_id=?")->execute([$phone, $userId]);
            }
            $pdo->commit();
            flash("success", "Phone number added.");
        } elseif ($action === "update_phone") {
            $phoneId = (int) ($_POST["phone_id"] ?? 0);
            $phone = trim($_POST["phone"] ?? "");
            if (!profile_valid_phone($phone)) {
                throw new Exception("Enter a valid phone number with at least 5 digits.");
            }
            $pdo->beginTransaction();
            $q = $pdo->prepare("SELECT phone_id,is_primary FROM user_phones WHERE phone_id=? AND user_id=? FOR UPDATE");
            $q->execute([$phoneId, $userId]);
            $contact = $q->fetch();
            if (!$contact) {
                throw new Exception("Phone number not found.");
            }
            $q = $pdo->prepare("SELECT phone_id FROM user_phones WHERE user_id=? AND phone=? AND phone_id<>? LIMIT 1");
            $q->execute([$userId, $phone, $phoneId]);
            if ($q->fetchColumn()) {
                throw new Exception("That phone number is already on your profile.");
            }
            $pdo->prepare("UPDATE user_phones SET phone=? WHERE phone_id=? AND user_id=?")->execute([$phone, $phoneId, $userId]);
            if ($contact["is_primary"]) {
                $pdo->prepare("UPDATE users SET phone=? WHERE user_id=?")->execute([$phone, $userId]);
            }
            $pdo->commit();
            flash("success", "Phone number updated.");
        } elseif ($action === "make_primary_phone") {
            $phoneId = (int) ($_POST["phone_id"] ?? 0);
            $pdo->beginTransaction();
            $q = $pdo->prepare("SELECT phone FROM user_phones WHERE phone_id=? AND user_id=? FOR UPDATE");
            $q->execute([$phoneId, $userId]);
            $phone = $q->fetchColumn();
            if (!$phone) {
                throw new Exception("Phone number not found.");
            }
            $pdo->prepare("UPDATE user_phones SET is_primary=0 WHERE user_id=?")->execute([$userId]);
            $pdo->prepare("UPDATE user_phones SET is_primary=1 WHERE phone_id=? AND user_id=?")->execute([$phoneId, $userId]);
            $pdo->prepare("UPDATE users SET phone=? WHERE user_id=?")->execute([$phone, $userId]);
            $pdo->commit();
            flash("success", "Primary phone number updated.");
        } elseif ($action === "delete_phone") {
            $phoneId = (int) ($_POST["phone_id"] ?? 0);
            $pdo->beginTransaction();
            $q = $pdo->prepare("SELECT phone_id,phone,is_primary FROM user_phones WHERE user_id=? ORDER BY phone_id FOR UPDATE");
            $q->execute([$userId]);
            $phones = $q->fetchAll();
            $target = null;
            foreach ($phones as $contact) {
                if ((int) $contact["phone_id"] === $phoneId) {
                    $target = $contact;
                    break;
                }
            }
            if (!$target) {
                throw new Exception("Phone number not found.");
            }
            if (count($phones) <= 1) {
                throw new Exception("At least one phone number must remain on your profile.");
            }
            $pdo->prepare("DELETE FROM user_phones WHERE phone_id=? AND user_id=?")->execute([$phoneId, $userId]);
            if ($target["is_primary"]) {
                $replacement = null;
                foreach ($phones as $contact) {
                    if ((int) $contact["phone_id"] !== $phoneId) {
                        $replacement = $contact;
                        break;
                    }
                }
                $pdo->prepare("UPDATE user_phones SET is_primary=1 WHERE phone_id=?")->execute([$replacement["phone_id"]]);
                $pdo->prepare("UPDATE users SET phone=? WHERE user_id=?")->execute([$replacement["phone"], $userId]);
            }
            $pdo->commit();
            flash("success", "Phone number deleted.");
        } else {
            throw new Exception("Unsupported profile action.");
        }

        header("Location: profile_edit.php");
        exit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $err = $e instanceof PDOException ? "Could not save the change. Check for duplicate contact details and try again." : $e->getMessage();
    }
}

$q = $pdo->prepare(
    "SELECT u.*,GROUP_CONCAT(DISTINCT r.role_name ORDER BY r.role_name SEPARATOR ', ') user_type FROM users u LEFT JOIN user_roles ur ON ur.user_id=u.user_id LEFT JOIN roles r ON r.role_id=ur.role_id WHERE u.user_id=? GROUP BY u.user_id",
);
$q->execute([$userId]);
$u = $q->fetch();
$q = $pdo->prepare("SELECT * FROM user_emails WHERE user_id=? ORDER BY is_primary DESC,email_id");
$q->execute([$userId]);
$emails = $q->fetchAll();
$q = $pdo->prepare("SELECT * FROM user_phones WHERE user_id=? ORDER BY is_primary DESC,phone_id");
$q->execute([$userId]);
$phones = $q->fetchAll();
$initial = strtoupper(substr(trim($u["full_name"] ?? "U"), 0, 1));
$profilePhotoUrl = $u["profile_photo"] ?? "";
$profilePhotoExists = str_starts_with($profilePhotoUrl, "uploads/avatars/") && is_file(__DIR__ . DIRECTORY_SEPARATOR . str_replace("/", DIRECTORY_SEPARATOR, $profilePhotoUrl));
require "includes/header.php";
?>
<?php if (!$isEditing): ?>
<div class="profile-page">
    <section class="profile-hero">
        <div class="profile-person">
            <div class="profile-avatar profile-avatar-large">
                <?php if ($profilePhotoExists): ?>
                    <img src="/scsms_V1/<?= e($profilePhotoUrl) ?>" alt="Profile photo of <?= e($u["full_name"]) ?>">
                <?php else: ?>
                    <span><?= e($initial) ?></span>
                <?php endif; ?>
            </div>
            <div class="profile-person-copy">
                <p class="profile-eyebrow">Account profile</p>
                <h1><?= e($u["full_name"]) ?></h1>
                <div class="profile-meta"><span class="profile-type"><?= e($u["user_type"] ?: "User") ?></span><span>Member since <?= e(date("M Y", strtotime($u["created_at"]))) ?></span></div>
            </div>
        </div>
        <a class="btn profile-edit-cta" href="profile_edit.php"><span aria-hidden="true">✎</span> Edit profile</a>
    </section>

    <div class="profile-summary-grid">
        <section class="profile-card">
            <div class="profile-card-heading"><div><p class="profile-eyebrow">Contact</p><h2>Contact details</h2></div><span class="profile-card-mark" aria-hidden="true">@</span></div>
            <div class="profile-contact-list">
                <?php foreach ($emails as $contact): ?>
                    <div class="profile-contact-item"><span class="profile-contact-icon" aria-hidden="true">@</span><span class="profile-contact-copy"><small>Email<?= $contact["is_primary"] ? " · Primary" : "" ?></small><strong><?= e($contact["email"]) ?></strong></span></div>
                <?php endforeach; ?>
                <?php foreach ($phones as $contact): ?>
                    <div class="profile-contact-item"><span class="profile-contact-icon" aria-hidden="true">#</span><span class="profile-contact-copy"><small>Phone<?= $contact["is_primary"] ? " · Primary" : "" ?></small><strong><?= e($contact["phone"]) ?></strong></span></div>
                <?php endforeach; ?>
                <?php if (!$emails && !$phones): ?><p class="profile-empty">No contact details have been added.</p><?php endif; ?>
            </div>
        </section>

        <section class="profile-card">
            <div class="profile-card-heading"><div><p class="profile-eyebrow">Account</p><h2>Account details</h2></div><span class="profile-card-mark" aria-hidden="true">✓</span></div>
            <dl class="profile-detail-list">
                <div><dt>Account type</dt><dd><?= e($u["user_type"] ?: "User") ?></dd></div>
                <div><dt>Primary email</dt><dd><?= e($u["email"]) ?></dd></div>
                <div><dt>Primary phone</dt><dd><?= e($u["phone"] ?: "Not set") ?></dd></div>
                <div><dt>Account status</dt><dd><span class="profile-status <?= $u["is_active"] ? "is-active" : "is-inactive" ?>"><?= $u["is_active"] ? "Active" : "Deactivated" ?></span></dd></div>
            </dl>
        </section>
    </div>
</div>
<?php require "includes/footer.php"; return; ?>
<?php endif; ?>
<div class="profile-edit-page">
    <div class="profile-edit-heading">
        <div><p class="profile-eyebrow">Account settings</p><h1>Edit profile</h1><p>Update your photo, personal details, contact methods, and password.</p></div>
        <a class="btn secondary" href="profile.php">Back to profile</a>
    </div>
    <?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>
    <?php if (!empty($_SESSION["flash"])): ?><?php show_flash(); ?><?php endif; ?>
    <section class="profile-card profile-photo-card">
        <div class="profile-photo-preview">
            <div class="profile-avatar profile-avatar-medium" data-profile-preview>
                <?php if ($profilePhotoExists): ?><img src="/scsms_V1/<?= e($profilePhotoUrl) ?>" alt="Current profile photo">
                <?php else: ?><span><?= e($initial) ?></span><?php endif; ?>
            </div>
            <div><p class="profile-eyebrow">Personalize</p><h2>Profile photo</h2><p>JPG, PNG, or WebP · maximum 2 MB</p></div>
        </div>
        <form method="post" enctype="multipart/form-data" class="profile-photo-form">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <input type="hidden" name="action" value="update_avatar">
            <label class="profile-file-picker"><input type="file" name="profile_photo" data-profile-photo accept="image/jpeg,image/png,image/webp" required><span>Choose photo</span></label>
            <button class="btn">Upload photo</button>
        </form>
    </section>
<div class="profile-edit-grid">
    <section class="profile-card">
        <div class="profile-card-heading"><div><p class="profile-eyebrow">Personal details</p><h2>Your name</h2></div></div>
        <?php if ($err): ?><div class="alert error"><?= e($err) ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <input type="hidden" name="action" value="update_profile">
            <label for="full-name">Name</label>
            <input id="full-name" name="full_name" value="<?= e($u["full_name"]) ?>" required>
            <button class="btn">Save name</button>
        </form>
    </section>

    <section class="profile-card">
        <div class="profile-card-heading"><div><p class="profile-eyebrow">Sign-in and contact</p><h2>Email addresses</h2></div></div>
        <?php foreach ($emails as $contact): ?>
            <div class="contact-row">
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= csrf() ?>">
                    <input type="hidden" name="action" value="update_email">
                    <input type="hidden" name="email_id" value="<?= (int) $contact["email_id"] ?>">
                    <label for="email-<?= (int) $contact["email_id"] ?>">Email address<?= $contact["is_primary"] ? " (primary, used for sign-in)" : "" ?></label>
                    <input id="email-<?= (int) $contact["email_id"] ?>" type="email" name="email" value="<?= e($contact["email"]) ?>" required>
                    <button class="btn">Update email</button>
                </form>
                <?php if (!$contact["is_primary"]): ?>
                    <form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="make_primary_email"><input type="hidden" name="email_id" value="<?= (int) $contact["email_id"] ?>"><button class="btn secondary">Make primary</button></form>
                <?php endif; ?>
                <form method="post" onsubmit="return confirm('Delete this email address?')"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="delete_email"><input type="hidden" name="email_id" value="<?= (int) $contact["email_id"] ?>"><button class="btn danger" <?= count($emails) <= 1 ? "disabled title=\"At least one email address is required\"" : "" ?>>Delete email</button></form>
            </div>
        <?php endforeach; ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <input type="hidden" name="action" value="add_email">
            <label for="new-email">Add email address</label>
            <input id="new-email" type="email" name="email" required>
            <button class="btn">Add email</button>
        </form>
    </section>

    <section class="profile-card">
        <div class="profile-card-heading"><div><p class="profile-eyebrow">Reach you</p><h2>Phone numbers</h2></div></div>
        <?php foreach ($phones as $contact): ?>
            <div class="contact-row">
                <form method="post">
                    <input type="hidden" name="csrf" value="<?= csrf() ?>">
                    <input type="hidden" name="action" value="update_phone">
                    <input type="hidden" name="phone_id" value="<?= (int) $contact["phone_id"] ?>">
                    <label for="phone-<?= (int) $contact["phone_id"] ?>">Phone number<?= $contact["is_primary"] ? " (primary)" : "" ?></label>
                    <input id="phone-<?= (int) $contact["phone_id"] ?>" type="tel" name="phone" value="<?= e($contact["phone"]) ?>" required>
                    <button class="btn">Update phone</button>
                </form>
                <?php if (!$contact["is_primary"]): ?>
                    <form method="post"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="make_primary_phone"><input type="hidden" name="phone_id" value="<?= (int) $contact["phone_id"] ?>"><button class="btn secondary">Make primary</button></form>
                <?php endif; ?>
                <form method="post" onsubmit="return confirm('Delete this phone number?')"><input type="hidden" name="csrf" value="<?= csrf() ?>"><input type="hidden" name="action" value="delete_phone"><input type="hidden" name="phone_id" value="<?= (int) $contact["phone_id"] ?>"><button class="btn danger" <?= count($phones) <= 1 ? "disabled title=\"At least one phone number is required\"" : "" ?>>Delete phone</button></form>
            </div>
        <?php endforeach; ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <input type="hidden" name="action" value="add_phone">
            <label for="new-phone">Add phone number</label>
            <input id="new-phone" type="tel" name="phone" required>
            <button class="btn">Add phone</button>
        </form>
    </section>

    <section class="profile-card">
        <div class="profile-card-heading"><div><p class="profile-eyebrow">Security</p><h2>Change password</h2></div></div>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <input type="hidden" name="action" value="change_password">
            <label for="current-password">Current password</label>
            <input id="current-password" type="password" name="current_password" required autocomplete="current-password">
            <label for="new-password">New password</label>
            <input id="new-password" type="password" name="new_password" minlength="8" required autocomplete="new-password">
            <label for="confirm-password">Confirm new password</label>
            <input id="confirm-password" type="password" name="confirm_password" minlength="8" required autocomplete="new-password">
            <button class="btn">Update password</button>
        </form>
    </section>
</div>
</div>
<?php require "includes/footer.php"; ?>
