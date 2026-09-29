<?php
require_once __DIR__ . "/../includes/functions.php";
require_login();
$id = (int) ($_GET["id"] ?? 0);

$q = $pdo->prepare(
    "SELECT c.*,d.department_name,cc.category_name,st.student_number,st.user_id student_user FROM complaints c LEFT JOIN departments d ON d.department_id=c.department_id JOIN complaint_categories cc ON cc.category_id=c.category_id JOIN students st ON st.student_id=c.student_id WHERE c.complaint_id=?",
);
$q->execute([$id]);
$c = $q->fetch();

if (!$c) {
    exit("Complaint not found.");
}

$isOwner = $c["student_user"] == $_SESSION["user_id"];
$as = current_assignment($pdo, $id);

if ($_SESSION["role"] === "Student" && !$isOwner) {
    exit("Access denied.");
}

if ($_SESSION["role"] === "Department Head") {
    $departmentQuery = $pdo->prepare(
        "SELECT department_id FROM staff WHERE user_id=? AND status='ACTIVE'",
    );
    $departmentQuery->execute([$_SESSION["user_id"]]);
    if ((int) $departmentQuery->fetchColumn() !== (int) $c["department_id"]) {
        exit("Access denied.");
    }
}

if (
    in_array($_SESSION["role"], ["Staff", "Department Head"]) &&
    !$as &&
    $_SESSION["role"] === "Staff"
) {
    exit("Access denied.");
}

$h = $pdo->prepare(
    "SELECT h.*,u.full_name FROM complaint_status_history h JOIN users u ON u.user_id=h.changed_by WHERE complaint_id=? ORDER BY changed_at",
);
$h->execute([$id]);
$hist = $h->fetchAll();

$a = $pdo->prepare(
    "SELECT * FROM attachments WHERE complaint_id=? ORDER BY uploaded_at",
);
$a->execute([$id]);
$atts = $a->fetchAll();

$f = $pdo->prepare("SELECT * FROM feedback WHERE complaint_id=?");
$f->execute([$id]);
$feedback = $f->fetch();

require "../includes/header.php";
?>
<div class="container complaint-detail-page">
    <div class="two">
        <div class="card">
            <h1><?= e($c["ticket_number"]) ?></h1>
            <h2><?= e($c["title"]) ?></h2>
            <p><?= nl2br(e($c["description"])) ?></p>

            <p>
                <?= badge($c["status"]) ?> &nbsp; <b><?= e($c["priority"]) ?></b>
            </p>

            <p>
                <b>Department:</b> <?= e($c["department_name"]) ?><br>
                <b>Category:</b> <?= e($c["category_name"]) ?><br>
                <b>Location:</b> <?= e($c["location"]) ?><br>
                <b>Submitted:</b> <?= e($c["submitted_at"]) ?>
            </p>

            <?php if ($as): ?>
                <p><b>Assigned Staff:</b> <?= e($as["full_name"]) ?></p>
            <?php endif; ?>

            <?php if ($c["resolution_description"]): ?>
                <div class="alert">
                    <b>Resolution:</b><br>
                    <?= nl2br(e($c["resolution_description"])) ?>
                </div>
            <?php endif; ?>

            <?php if ($atts): ?>
                <h3>Attachments</h3>
                <?php foreach ($atts as $x): ?>
                    <p>
                        <a href="/scsms_V1/download.php?id=<?= $x["attachment_id"] ?>"><?= e($x["original_name"]) ?></a>
                    </p>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Status History</h2>
            <div class="timeline">
                <?php foreach ($hist as $x): ?>
                    <div>
                        <?= badge($x["new_status"]) ?><br>
                        <b><?= e($x["full_name"]) ?></b><br>
                        <span class="muted"><?= e($x["changed_at"]) ?></span>
                        <?php if ($x["remarks"]): ?>
                            <br><?= e($x["remarks"]) ?>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if ($isOwner && in_array($c["status"], ["RESOLVED", "CLOSED"], true) && !$feedback): ?>
                <hr>
                <h3>Feedback</h3>
                <form method="post" action="/scsms_V1/student/feedback.php">
                    <input type="hidden" name="csrf" value="<?= csrf() ?>">
                    <input type="hidden" name="complaint_id" value="<?= $id ?>">
                    <fieldset class="star-rating">
                        <legend>Rate this resolution</legend>
                        <div class="star-rating-options">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input id="feedback-rating-<?= $i ?>" type="radio" name="rating" value="<?= $i ?>" required>
                                <label for="feedback-rating-<?= $i ?>" title="<?= $i ?> out of 5 stars" aria-label="<?= $i ?> out of 5 stars">★</label>
                            <?php endfor; ?>
                        </div>
                    </fieldset>
                    <label>Comment</label>
                    <textarea name="comment"></textarea>
                    <button class="btn">Submit Feedback</button>
                </form>
            <?php elseif ($feedback): ?>
                <p><b>Feedback:</b> <?= $feedback["rating"] ?>/5 — <?= e($feedback["comment"]) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require "../includes/footer.php"; ?>
