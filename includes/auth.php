<?php
function requireLogin() {
    if (!isset($_SESSION['user_id'])) { header('Location: ../index.php'); exit; }
}
function requireRole($roles) {
    requireLogin();
    if (!in_array($_SESSION['role'], (array)$roles, true)) {
        header('Location: ../index.php'); exit;
    }
}
function isAdmin()     { return ($_SESSION['role'] ?? '') === 'admin'; }
function isPrincipal() { return ($_SESSION['role'] ?? '') === 'principal'; }
function isLecturer()  { return ($_SESSION['role'] ?? '') === 'lecturer'; }
function isStudent()   { return ($_SESSION['role'] ?? '') === 'student'; }
?>