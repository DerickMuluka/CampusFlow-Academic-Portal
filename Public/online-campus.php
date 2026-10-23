<?php $pageTitle = 'Online Campus — Rifkins College'; ?>
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
            <span class="pub-section-eyebrow">Online Campus</span>
            <h2>Study from Anywhere in the World</h2>
            <p class="lead">Rifkins College's online campus brings UK and Kenyan qualifications to students across Africa and beyond.</p>
        </div>

        <div class="pub-features">
            <div class="pub-feature">
                <div class="pub-feature-icon">🌍</div>
                <h3>International Students Welcome</h3>
                <p>Besides Kenyan students, we have learners from various countries taking UK online courses on our seamless e-learning campus. Study from anywhere with an internet connection.</p>
            </div>
            <div class="pub-feature">
                <div class="pub-feature-icon">📚</div>
                <h3>e-Dashboard</h3>
                <p>Access notes for all courses via your online dashboard — no dictation, no photocopying, no copying from the board. Simply log in and access your learning materials.</p>
            </div>
            <div class="pub-feature">
                <div class="pub-feature-icon">📖</div>
                <h3>eLibrary</h3>
                <p>A state-of-the-art digital library with thousands of academic resources — available 24/7 from anywhere.</p>
            </div>
            <div class="pub-feature">
                <div class="pub-feature-icon">📝</div>
                <h3>ePastpapers</h3>
                <p>Past exam papers organised by course and unit — the fastest way to prepare for assessments.</p>
            </div>
            <div class="pub-feature">
                <div class="pub-feature-icon">📊</div>
                <h3>Results Portal</h3>
                <p>Check CAT, assignment, and final exam results securely. View your full academic transcript any time.</p>
            </div>
            <div class="pub-feature">
                <div class="pub-feature-icon">🎓</div>
                <h3>UK & Local Qualifications</h3>
                <p>Online students earn the same ICM (UK), ABMA (UK), KNEC, and CSK certificates as on-campus students.</p>
            </div>
        </div>

        <div style="margin-top:50px;">
            <h2 style="text-align:center;font-family:var(--font-display);color:var(--primary);margin-bottom:36px;">How Online Learning Works</h2>
            <div class="pub-features">
                <div class="pub-feature" style="text-align:center;">
                    <div style="font-size:2.5rem;color:var(--accent);font-weight:800;font-family:var(--font-display);">1</div>
                    <h3>Apply & Enrol</h3>
                    <p>Complete the <a href="apply.php" style="color:var(--primary-light);font-weight:700;">online application</a> and pay the Kshs 500 fee via M-Pesa.</p>
                </div>
                <div class="pub-feature" style="text-align:center;">
                    <div style="font-size:2.5rem;color:var(--accent);font-weight:800;font-family:var(--font-display);">2</div>
                    <h3>Get Your Login</h3>
                    <p>Once enrolled, you receive portal credentials from the administrator. Set a new password on first login.</p>
                </div>
                <div class="pub-feature" style="text-align:center;">
                    <div style="font-size:2.5rem;color:var(--accent);font-weight:800;font-family:var(--font-display);">3</div>
                    <h3>Learn & Assess</h3>
                    <p>Access notes, submit assignments, take proctored exams, and track your transcript — all from your dashboard.</p>
                </div>
            </div>
        </div>

        <div style="text-align:center;margin-top:50px;">
            <a href="../login.php" class="btn-hero btn-hero-primary">Login to Student Portal</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/public-footer.php'; ?>
</body>
</html>