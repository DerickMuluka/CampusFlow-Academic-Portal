<?php $pageTitle = 'eLibrary — Rifkins College'; ?>
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
            <span class="pub-section-eyebrow">eLibrary</span>
            <h2>No Dusty Old Books</h2>
            <p class="lead">A state-of-the-art digital library with thousands of academic resources — available 24/7 from anywhere.</p>
        </div>

        <div class="pub-features">
            <div class="pub-feature">
                <div class="pub-feature-icon">📚</div>
                <h3>Thousands of Titles</h3>
                <p>Access textbooks, journals, and reference materials across all our course areas — Accounting, Business, Hospitality, IT, Shipping, Social Work, and more.</p>
            </div>
            <div class="pub-feature">
                <div class="pub-feature-icon">🔍</div>
                <h3>Search & Filter</h3>
                <p>Find exactly what you need quickly — search by title, author, subject, or course. Everything is catalogued and organised for easy access.</p>
            </div>
            <div class="pub-feature">
                <div class="pub-feature-icon">📱</div>
                <h3>Read Anywhere</h3>
                <p>The eLibrary works on any device — desktop, tablet, or phone. Study during your commute or from the comfort of home.</p>
            </div>
            <div class="pub-feature">
                <div class="pub-feature-icon">🎯</div>
                <h3>Exam-Focused Resources</h3>
                <p>Curated revision notes, model answers, and study guides specifically tailored to ICM (UK), ABMA (UK), KNEC, and CSK examinations.</p>
            </div>
            <div class="pub-feature">
                <div class="pub-feature-icon">🔐</div>
                <h3>Exclusive to Students</h3>
                <p>The eLibrary is FREE to all enrolled Rifkins College students. Log in with your portal credentials to access it.</p>
            </div>
            <div class="pub-feature">
                <div class="pub-feature-icon">💬</div>
                <h3>Need Help?</h3>
                <p>Contact our library team via WhatsApp <strong>0715 043739</strong> or email <a href="mailto:info@rifkinscollege.com" style="color:var(--primary-light);font-weight:700;">info@rifkinscollege.com</a>.</p>
            </div>
        </div>

        <div style="text-align:center;margin-top:50px;">
            <h2 style="font-family:var(--font-display);color:var(--primary);margin-bottom:16px;">Access the eLibrary</h2>
            <p style="color:var(--muted);max-width:560px;margin:0 auto 24px;">Log in with your student credentials to browse and read all resources.</p>
            <a href="../index.php" class="btn-hero btn-hero-primary">Login to Student Portal</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/public-footer.php'; ?>
</body>
</html>