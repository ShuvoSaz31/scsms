<?php
require_once __DIR__ . "/../includes/functions.php";
require_role("Student");

$cats = $pdo
    ->query("SELECT * FROM complaint_categories WHERE is_active=1 ORDER BY category_name")
    ->fetchAll();
$deps = $pdo
    ->query("SELECT * FROM departments WHERE is_active=1 ORDER BY department_name")
    ->fetchAll();
$err = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    check_csrf();
    try {
        $st = $pdo->prepare("SELECT student_id FROM students WHERE user_id=?");
        $st->execute([$_SESSION["user_id"]]);
        $sid = $st->fetchColumn();
        if (!$sid) {
            throw new Exception("Student profile not found.");
        }

        $pdo->beginTransaction();
        $pdo->prepare(
            "INSERT INTO complaints(ticket_number,student_id,category_id,subcategory_id,department_id,title,description,priority,location) VALUES(?,?,?,?,?,?,?,?,?)",
        )->execute([
            "TEMP-" . $sid,
            $sid,
            $_POST["category_id"],
            $_POST["subcategory_id"] ?: null,
            $_POST["department_id"],
            trim($_POST["title"]),
            trim($_POST["description"]),
            $_POST["priority"],
            trim($_POST["location"]),
        ]);
        $id = $pdo->lastInsertId();
        $ticket = "CMP-" . date("Y") . "-" . str_pad($id, 6, "0", STR_PAD_LEFT);
        $pdo->prepare(
            "UPDATE complaints SET ticket_number=? WHERE complaint_id=?",
        )->execute([$ticket, $id]);
        add_history(
            $pdo,
            $id,
            null,
            "SUBMITTED",
            $_SESSION["user_id"],
            "Complaint submitted",
        );
        notify(
            $pdo,
            $_SESSION["user_id"],
            $id,
            "Complaint submitted",
            "Your complaint " . $ticket . " has been submitted.",
        );
        if (!empty($_FILES["attachment"]["name"])) {
            upload_file($pdo, $id, $_SESSION["user_id"], $_FILES["attachment"]);
        }
        $pdo->commit();
        flash("success", "Complaint submitted. Ticket: " . $ticket);
        header("Location: complaint.php?id=" . $id);
        exit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $err = $e->getMessage();
    }
}

require "../includes/header.php";
?>
<div class="container submit-complaint">
    <div class="page-heading">
        <div>
            <p class="eyebrow">STUDENT SERVICES / NEW REQUEST</p>
            <h1>Tell us what needs attention.</h1>
            <p class="muted">Your request will be routed to the department you select.</p>
        </div>
        <span class="form-mark" aria-hidden="true">S</span>
    </div>

    <section class="card complaint-form-card">
        <?php if ($err): ?>
            <div class="alert error"><?= e($err) ?></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?= csrf() ?>">
            <label for="complaint-title">Subject</label>
            <input id="complaint-title" name="title" placeholder="A short summary of the issue" required maxlength="200">

            <label for="complaint-description">What happened?</label>
            <textarea id="complaint-description" name="description" rows="7" placeholder="Share details that will help us understand and resolve your request." required></textarea>

            <div class="form-grid">
                <div>
                    <label for="complaint-category">Category</label>
                    <select id="complaint-category" name="category_id" required>
                        <option value="">Select a category</option>
                        <?php foreach ($cats as $category): ?>
                            <option value="<?= $category["category_id"] ?>"><?= e($category["category_name"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="complaint-department">Department</label>
                    <select id="complaint-department" name="department_id" required>
                        <option value="">Choose a department</option>
                        <?php foreach ($deps as $department): ?>
                            <option value="<?= $department["department_id"] ?>"><?= e($department["department_name"]) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="complaint-priority">Priority</label>
                    <select id="complaint-priority" name="priority">
                        <option>Low</option>
                        <option selected>Medium</option>
                        <option>High</option>
                        <option>Critical</option>
                    </select>
                </div>
                <div>
                    <label for="complaint-subcategory">Subcategory ID <span class="muted">(optional)</span></label>
                    <input id="complaint-subcategory" type="number" name="subcategory_id" min="1" placeholder="Optional">
                </div>
                <div>
                    <label for="complaint-location">Location <span class="muted">(optional)</span></label>
                    <input id="complaint-location" name="location" maxlength="200" placeholder="Building, room or area">
                </div>
            </div>

            <label for="complaint-attachment">Supporting file <span class="muted">(optional, max 5 MB)</span></label>
            <input id="complaint-attachment" type="file" name="attachment" accept=".pdf,.png,.jpg,.jpeg,.doc,.docx">

            <div class="form-actions">
                <button class="btn">Submit complaint</button>
                <span class="muted">Accepted: PDF, PNG, JPG, DOC, DOCX</span>
            </div>
        </form>
    </section>
</div>
<?php require "../includes/footer.php"; ?>