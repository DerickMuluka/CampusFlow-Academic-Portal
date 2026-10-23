<?php $pageTitle = 'Past Papers — Rifkins College'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $pageTitle ?></title>
<link rel="stylesheet" href="../assets/css/public.css">
</head>
<body>
<?php include __DIR__ . '/includes/public-header.php'; ?>

<section class="pub-section" style="padding-top:120px;">
    <div class="container">
        <div class="pub-section-header">
            <span class="pub-section-eyebrow">ePastpapers</span>
            <h2>Past Examination Papers</h2>
            <p class="lead">Revision made easier. Access past papers for your course and unit from your student dashboard.</p>
        </div>

        <div class="pub-features">
            <div class="pub-feature">
                <div class="pub-feature-icon">📝</div>
                <h3>Organised by Unit</h3>
                <p>Papers are sorted by course and unit so you can find exactly what you need to revise in seconds.</p>
            </div>
            <div class="pub-feature">
                <div class="pub-feature-icon">🎯</div>
                <h3>Exam-Focused</h3>
                <p>Past papers for ICM (UK), ABMA (UK), KNEC, and CSK examinations — the exact formats you will face in your exams.</p>
            </div>
            <div class="pub-feature">
                <div class="pub-feature-icon">🆓</div>
                <h3>Free to All Students</h3>
                <p>Available exclusively to enrolled Rifkins College students through the online campus. No cost, no limits.</p>
            </div>
        </div>

        <div style="text-align:center;margin-top:50px;">
            <h2 style="font-family:var(--font-display);color:var(--primary);margin-bottom:16px;">Access Past Papers</h2>
            <p style="color:var(--muted);max-width:560px;margin:0 auto 24px;">Past papers are available to enrolled students. Log in with your credentials to access the full repository.</p>
            <a href="../login.php" class="btn-hero btn-hero-primary">Login to Student Portal</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/public-footer.php'; ?>
</body>
</html>