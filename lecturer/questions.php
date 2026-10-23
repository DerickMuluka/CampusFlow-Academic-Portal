<?php
require_once '../config/db.php';
requireRole('lecturer');
$lid = (int)$_SESSION['user_id'];

$exam_id = (int)($_GET['exam'] ?? $_POST['exam_id'] ?? 0);
$exam = $conn->query("
    SELECT e.*, un.id AS unit_id, un.name AS unit_name
    FROM exams e JOIN units un ON un.id=e.unit_id
    JOIN lecturer_units lu ON lu.unit_id = e.unit_id AND lu.lecturer_id=$lid
    WHERE e.id=$exam_id
")->fetch_assoc();
if (!$exam) die('Exam not found or access denied.');

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    if ($action === 'add') {
        $qtext = trim($_POST['question_text'] ?? '');
        $a = trim($_POST['option_a'] ?? ''); $b = trim($_POST['option_b'] ?? '');
        $c = trim($_POST['option_c'] ?? ''); $d = trim($_POST['option_d'] ?? '');
        $correct = $_POST['correct_answer'] ?? 'A';
        $marks = (int)($_POST['marks'] ?? 1);
        if ($qtext && $a && $b && $c && $d) {
            $stmt = $conn->prepare("INSERT INTO questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer, marks) VALUES (?,?,?,?,?,?,?,?)");
            $stmt->bind_param('issssssi', $exam_id, $qtext, $a, $b, $c, $d, $correct, $marks);
            $stmt->execute(); $stmt->close();
            $msg = 'Question added.';
        }
    } elseif ($action === 'update') {
        $qid = (int)$_POST['qid'];
        $qtext = trim($_POST['question_text']);
        $a = trim($_POST['option_a']); $b = trim($_POST['option_b']);
        $c = trim($_POST['option_c']); $d = trim($_POST['option_d']);
        $correct = $_POST['correct_answer']; $marks = (int)$_POST['marks'];
        $stmt = $conn->prepare("UPDATE questions SET question_text=?, option_a=?, option_b=?, option_c=?, option_d=?, correct_answer=?, marks=? WHERE id=? AND exam_id=?");
        $stmt->bind_param('sssssssii', $qtext, $a, $b, $c, $d, $correct, $marks, $qid, $exam_id);
        $stmt->execute(); $stmt->close();
        $msg = 'Question updated.';
    }
}

if (isset($_GET['delete'])) {
    $qid = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM questions WHERE id=? AND exam_id=?");
    $stmt->bind_param('ii', $qid, $exam_id);
    $stmt->execute(); $stmt->close();
    redirect("questions.php?exam=$exam_id");
}

$editing = null;
if (isset($_GET['edit'])) {
    $qid = (int)$_GET['edit'];
    $editing = $conn->query("SELECT * FROM questions WHERE id=$qid AND exam_id=$exam_id")->fetch_assoc();
}

$questions = $conn->query("SELECT * FROM questions WHERE exam_id=$exam_id ORDER BY id")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Questions — <?= esc($exam['title']) ?></title>
<link rel="stylesheet" href="../assets/css/base.css">
<link rel="stylesheet" href="../assets/css/layout.css">
<link rel="stylesheet" href="../assets/css/components.css">
<link rel="stylesheet" href="../assets/css/responsive.css"></head>
<body>
<?php include '../includes/header.php'; ?>
<div class="app-layout">
<?php include '../includes/sidebar.php'; ?>
<main class="main-content">
    <h1><?= $editing ? 'Edit Question' : 'Add Question' ?></h1>
    <p class="subtitle">Exam: <?= esc($exam['title']) ?> (<?= esc($exam['unit_name']) ?>)</p>
    <?php if ($msg): ?><div class="alert-success"><?= esc($msg) ?></div><?php endif; ?>

    <form method="POST" class="card-form">
        <input type="hidden" name="exam_id" value="<?= $exam_id ?>">
        <input type="hidden" name="action" value="<?= $editing ? 'update' : 'add' ?>">
        <?php if ($editing): ?><input type="hidden" name="qid" value="<?= $editing['id'] ?>"><?php endif; ?>
        <div class="form-group"><label>Question Text</label><textarea name="question_text" rows="3" required><?= $editing ? esc($editing['question_text']) : '' ?></textarea></div>
        <div class="form-row">
            <div class="form-group"><label>Option A</label><input type="text" name="option_a" required value="<?= $editing ? esc($editing['option_a']) : '' ?>"></div>
            <div class="form-group"><label>Option B</label><input type="text" name="option_b" required value="<?= $editing ? esc($editing['option_b']) : '' ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group"><label>Option C</label><input type="text" name="option_c" required value="<?= $editing ? esc($editing['option_c']) : '' ?>"></div>
            <div class="form-group"><label>Option D</label><input type="text" name="option_d" required value="<?= $editing ? esc($editing['option_d']) : '' ?>"></div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Correct Answer</label>
                <select name="correct_answer">
                    <?php foreach (['A','B','C','D'] as $o): ?>
                        <option <?= ($editing && $editing['correct_answer']===$o)?'selected':'' ?>><?= $o ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Marks</label><input type="number" name="marks" value="<?= $editing ? (int)$editing['marks'] : 1 ?>" min="1" required></div>
        </div>
        <button type="submit" class="btn-primary"><?= $editing ? 'Update Question' : 'Add Question' ?></button>
        <?php if ($editing): ?><a class="link-btn" href="questions.php?exam=<?= $exam_id ?>" style="margin-left:12px;">Cancel</a><?php endif; ?>
    </form>

    <h2 style="margin-top:30px;">All Questions (<?= count($questions) ?>)</h2>
    <?php foreach ($questions as $i => $q): ?>
    <div class="question-card">
        <div class="q-header"><span class="q-num">Q<?= $i+1 ?> · <?= $q['marks'] ?> mark<?= $q['marks']>1?'s':'' ?></span>
        <span><a class="link-btn" href="?exam=<?= $exam_id ?>&edit=<?= $q['id'] ?>">Edit</a>
        <a class="link-btn" href="?exam=<?= $exam_id ?>&delete=<?= $q['id'] ?>" onclick="return confirm('Delete question?')">Delete</a></span></div>
        <p class="q-text"><?= esc($q['question_text']) ?></p>
        <div class="options-display">
        <?php foreach (['A','B','C','D'] as $o): ?>
            <div class="option-row <?= $q['correct_answer']===$o?'correct-opt':'' ?>">
                <span class="option-badge"><?= $o ?></span>
                <span><?= esc($q['option_'.strtolower($o)]) ?></span>
                <?php if ($q['correct_answer']===$o): ?><span class="opt-tag">✓ Correct</span><?php endif; ?>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</main>
</div>
</body>
</html>