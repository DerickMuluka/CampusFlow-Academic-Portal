<?php
require_once __DIR__ . '/../config/db.php';
$pageTitle = 'Online Application — Rifkins College';
$success = false;
$error = '';
$submittedName = '';
$submittedCourse = '';

// Fetch all courses from the database, grouped by department
$courseRows = $conn->query("
    SELECT c.id, c.name, c.level, c.exam_body, d.name AS dept_name
    FROM courses c
    JOIN departments d ON d.id = c.department_id
    ORDER BY d.name, c.name
")->fetch_all(MYSQLI_ASSOC);

$groupedCourses = [];
foreach ($courseRows as $row) {
    $groupedCourses[$row['dept_name']][] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $campus = $_POST['campus'] ?? '';
    $kcse = trim($_POST['kcse_grade'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($fullName === '' || $email === '' || $phone === '' || $course === '' || $campus === '') {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Verify the selected course exists in the database
        $check = $conn->prepare("SELECT id FROM courses WHERE name = ? LIMIT 1");
        $check->bind_param('s', $course);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$exists) {
            $error = 'The selected course is no longer available. Please refresh and choose again.';
        } else {
            $stmt = $conn->prepare("INSERT INTO applications (full_name, email, phone, course_interest, preferred_campus, kcse_grade, message) VALUES (?,?,?,?,?,?,?)");
            $stmt->bind_param('sssssss', $fullName, $email, $phone, $course, $campus, $kcse, $message);
            if ($stmt->execute()) {
                $success = true;
                $submittedName = $fullName;
                $submittedCourse = $course;
            } else {
                $error = 'We could not save your application. Please try again or call 0715 043739.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?></title>
<link rel="stylesheet" href="../assets/css/public.css">
<style>
    .apply-card { background: var(--white); border-radius: var(--radius); padding: 36px; box-shadow: var(--shadow); max-width: 720px; margin: 0 auto; border-top: 5px solid var(--accent); }
    .apply-card .form-group { margin-bottom: 18px; }
    .apply-card label { display: block; font-weight: 700; font-size: .85rem; color: var(--primary); margin-bottom: 6px; }
    .apply-card .req { color: #c0392b; }
    .apply-card input, .apply-card select, .apply-card textarea {
        width: 100%; padding: 12px 14px; border: 2px solid var(--border); border-radius: 8px;
        font-size: .95rem; font-family: inherit; background: var(--white); transition: border-color .15s;
    }
    .apply-card input:focus, .apply-card select:focus, .apply-card textarea:focus { outline: none; border-color: var(--accent); }
    .apply-card .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .apply-card .btn-primary { background: var(--accent); color: var(--primary); font-weight: 800; padding: 15px 32px; border: none; border-radius: 8px; font-size: 1rem; cursor: pointer; transition: background .15s; width: 100%; margin-top: 8px; letter-spacing: .5px; }
    .apply-card .btn-primary:hover { background: var(--accent-hover); }
    .alert-ok { background: #e6f5ea; color: #1e7e34; padding: 18px 20px; border-radius: 8px; border-left: 4px solid #1e7e34; margin-bottom: 20px; font-size: .92rem; }
    .alert-ok h3 { color: #1e7e34; margin-bottom: 8px; }
    .alert-err { background: #fde8e8; color: #c0392b; padding: 14px 18px; border-radius: 8px; border-left: 4px solid #c0392b; margin-bottom: 20px; font-size: .9rem; }
    .fee-note { background: #fff8e1; padding: 14px 18px; border-radius: 8px; border-left: 4px solid var(--accent); font-size: .88rem; margin-bottom: 24px; line-height: 1.6; }
    @media (max-width: 600px) { .apply-card { padding: 24px 18px; } .apply-card .form-row { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<?php include __DIR__ . '/includes/public-header.php'; ?>

<section class="pub-section" style="padding-top:120px;">
    <div class="container">
        <div class="pub-section-header">
            <span class="pub-section-eyebrow">Online Application</span>
            <h2>Apply to Rifkins College</h2>
            <p class="lead">Fill in the form below and our admissions team will get back to you within 24 hours.</p>
        </div>

        <div class="apply-card">
            <?php if ($success): ?>
                <div class="alert-ok">
                    <h3>✓ Application Received!</h3>
                    <p>Thank you, <strong><?= htmlspecialchars($submittedName) ?></strong>. Your application for <strong><?= htmlspecialchars($submittedCourse) ?></strong> has been saved.</p>
                    <p style="margin-top:10px;"><strong>Next step:</strong> Pay the Kshs 500 application fee via:</p>
                    <p style="background:#fff;padding:12px;border-radius:6px;margin-top:8px;font-family:monospace;">
                        M-Pesa PayBill: <strong>4088921</strong><br>
                        Account: <strong><?= htmlspecialchars(strtoupper(explode(' ', $submittedName)[0])) ?></strong> (your first name, no spaces)
                    </p>
                    <p style="margin-top:12px;">The admissions team reviews applications daily. You will receive your admission number by email once reviewed.</p>
                    <p style="margin-top:16px;"><a href="index.php" class="btn-hero btn-hero-primary">Back to Home</a></p>
                </div>
            <?php else: ?>
                <?php if ($error): ?><div class="alert-err"><?= htmlspecialchars($error) ?></div><?php endif; ?>

                <div class="fee-note">
                    <strong>📌 Application Fee:</strong> Kshs 500 payable via M-Pesa PayBill <strong>4088921</strong>, Account: your first name (no spaces). Pay after submitting this form.
                </div>

                <form method="POST">
                    <div class="form-group">
                        <label>Full Name <span class="req">*</span></label>
                        <input type="text" name="full_name" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" placeholder="e.g. Amina Hassan Ali">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Email Address <span class="req">*</span></label>
                            <input type="email" name="email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com">
                        </div>
                        <div class="form-group">
                            <label>Phone Number <span class="req">*</span></label>
                            <input type="tel" name="phone" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="07XX XXX XXX">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Course of Interest <span class="req">*</span></label>
                        <select name="course" required>
                            <option value="">— Select a course —</option>
                            <?php foreach ($groupedCourses as $deptName => $courses): ?>
                                <optgroup label="<?= htmlspecialchars($deptName) ?>">
                                    <?php foreach ($courses as $c): ?>
                                        <option value="<?= htmlspecialchars($c['name']) ?>"
                                            <?= ($_POST['course'] ?? '') === $c['name'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['name']) ?>
                                            <?php if ($c['level']): ?> (<?= htmlspecialchars($c['level']) ?>)<?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                        <?php if (empty($courseRows)): ?>
                            <small style="color:#c0392b;">No courses available yet. Please contact the college.</small>
                        <?php endif; ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Preferred Campus <span class="req">*</span></label>
                            <select name="campus" required>
                                <option value="">— Select campus —</option>
                                <option <?= ($_POST['campus'] ?? '') === 'Mombasa Main' ? 'selected' : '' ?>>Mombasa Main</option>
                                <option <?= ($_POST['campus'] ?? '') === 'Mariakani' ? 'selected' : '' ?>>Mariakani</option>
                                <option <?= ($_POST['campus'] ?? '') === 'Diani' ? 'selected' : '' ?>>Diani</option>
                                <option <?= ($_POST['campus'] ?? '') === 'Online' ? 'selected' : '' ?>>Online</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>KCSE Grade (if applicable)</label>
                            <input type="text" name="kcse_grade" value="<?= htmlspecialchars($_POST['kcse_grade'] ?? '') ?>" placeholder="e.g. C+">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Message / Questions (optional)</label>
                        <textarea name="message" rows="4"><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn-primary">Submit Application</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/public-footer.php'; ?>
</body>
</html>