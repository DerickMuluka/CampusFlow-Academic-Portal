<?php
$role = $_SESSION['role'] ?? '';
$current = basename($_SERVER['PHP_SELF']);

// Build base URL: /rifkins_exam/  (works under any folder name)
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])); // e.g. /rifkins_exam/admin
$basePath = preg_replace('#/(admin|principal|lecturer|student|library|api)$#', '', $scriptDir);
if ($basePath === '' || $basePath === '/') $basePath = '/rifkins_exam';
$BASE = rtrim($basePath, '/') . '/';

function navLink($href, $label, $current) {
    global $BASE;
    $active = ($current === basename($href)) ? ' class="active"' : '';
    echo "<a href=\"{$BASE}$href\"$active>$label</a>";
}
?>
<aside class="sidebar" id="sidebar">
    <nav>
    <?php if ($role === 'admin'): ?>
        <div class="sidebar-section">System</div>
        <?php navLink('admin/dashboard.php', 'Dashboard', $current); ?>
        <?php navLink('admin/applications.php', 'Online Applications', $current); ?>
        <?php navLink('admin/users.php', 'Manage Users', $current); ?>
        <?php navLink('admin/departments.php', 'Departments', $current); ?>
        <?php navLink('admin/courses.php', 'Courses', $current); ?>
        <?php navLink('admin/units.php', 'Units', $current); ?>
        <?php navLink('admin/results-monitor.php', 'All Results', $current); ?>
        <?php navLink('admin/audit-log.php', 'Activity Monitor', $current); ?>
        <div class="sidebar-section">Library</div>
        <?php navLink('library/index.php', 'eLibrary', $current); ?>
        <?php navLink('library/manage.php', 'Manage Library', $current); ?>
        <div class="sidebar-section">Account</div>
        <?php navLink('admin/profile.php', 'My Profile', $current); ?>

    <?php elseif ($role === 'principal'): ?>
        <div class="sidebar-section">Institution</div>
        <?php navLink('principal/dashboard.php', 'Dashboard', $current); ?>
        <?php navLink('principal/assign-lecturer.php', 'Assign Lecturers', $current); ?>
        <?php navLink('principal/notes.php', 'Manage Notes', $current); ?>
        <?php navLink('principal/tutorials.php', 'Tutorials', $current); ?>
        <?php navLink('principal/results-monitor.php', 'All Results', $current); ?>
        <?php navLink('principal/reports.php', 'Reports', $current); ?>
        <div class="sidebar-section">Library</div>
        <?php navLink('library/index.php', 'eLibrary', $current); ?>
        <?php navLink('library/manage.php', 'Manage Library', $current); ?>
        <div class="sidebar-section">Account</div>
        <?php navLink('principal/profile.php', 'My Profile', $current); ?>

    <?php elseif ($role === 'lecturer'): ?>
        <div class="sidebar-section">Teaching</div>
        <?php navLink('lecturer/dashboard.php', 'Dashboard', $current); ?>
        <?php navLink('lecturer/enroll-students.php', 'Enroll Students', $current); ?>
        <?php navLink('lecturer/exams.php', 'Exams', $current); ?>
        <?php navLink('lecturer/notes.php', 'Unit Notes', $current); ?>
        <?php navLink('lecturer/assignments.php', 'Assignments', $current); ?>
        <?php navLink('lecturer/pastpapers.php', 'Past Papers', $current); ?>
        <?php navLink('lecturer/online-sessions.php', 'Online Sessions', $current); ?>
        <?php navLink('lecturer/cat-scores.php', 'CAT & Final Scores', $current); ?>
        <?php navLink('lecturer/results.php', 'Student Results', $current); ?>
        <?php navLink('lecturer/results-monitor.php', 'All Results', $current); ?>
        <div class="sidebar-section">Library</div>
        <?php navLink('library/index.php', 'eLibrary', $current); ?>
        <?php navLink('library/upload.php', 'Upload Resource', $current); ?>
        <div class="sidebar-section">Account</div>
        <?php navLink('lecturer/profile.php', 'My Profile', $current); ?>

    <?php elseif ($role === 'student'): ?>
        <div class="sidebar-section">Learning</div>
        <?php navLink('student/dashboard.php', 'Dashboard', $current); ?>
        <?php navLink('student/notes.php', 'My Notes', $current); ?>
        <?php navLink('student/assignments.php', 'Assignments', $current); ?>
        <?php navLink('student/tutorials.php', 'Tutorials', $current); ?>
        <?php navLink('student/online-sessions.php', 'Online Classes', $current); ?>
        <?php navLink('student/results.php', 'My Results', $current); ?>
        <?php navLink('student/transcript.php', 'Transcript', $current); ?>
        <div class="sidebar-section">Library</div>
        <?php navLink('library/index.php', 'eLibrary', $current); ?>
        <div class="sidebar-section">Account</div>
        <?php navLink('student/profile.php', 'My Profile', $current); ?>
    <?php endif; ?>
    </nav>
</aside>