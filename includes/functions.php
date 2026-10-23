<?php
function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function getInitials($name) {
    $parts = preg_split('/\s+/', trim((string)$name));
    $init = '';
    if (isset($parts[0]) && $parts[0] !== '') $init .= strtoupper(substr($parts[0], 0, 1));
    if (isset($parts[1]) && $parts[1] !== '') $init .= strtoupper(substr($parts[1], 0, 1));
    return $init !== '' ? $init : 'U';
}

function redirect($path) { header("Location: $path"); exit; }

function canTakeExam($conn, $student_id, $exam_id) {
    $stmt = $conn->prepare("SELECT COUNT(*) c FROM attempts WHERE student_id=? AND exam_id=? AND status!='in_progress'");
    $stmt->bind_param('ii', $student_id, $exam_id);
    $stmt->execute();
    $count = (int)$stmt->get_result()->fetch_assoc()['c'];
    $stmt->close();
    if ($count === 0) return true;
    $stmt = $conn->prepare("SELECT retake_authorized FROM attempts WHERE student_id=? AND exam_id=? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('ii', $student_id, $exam_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row && (int)$row['retake_authorized'] === 1;
}

// NEW: check if student is enrolled in a unit
function isEnrolled($conn, $student_id, $unit_id) {
    $stmt = $conn->prepare("SELECT 1 FROM unit_enrollments WHERE student_id=? AND unit_id=?");
    $stmt->bind_param('ii', $student_id, $unit_id);
    $stmt->execute();
    $ok = $stmt->get_result()->fetch_assoc() !== null;
    $stmt->close();
    return $ok;
}

// NEW: check if lecturer owns a unit
function lecturerOwnsUnit($conn, $lecturer_id, $unit_id) {
    $stmt = $conn->prepare("SELECT 1 FROM lecturer_units WHERE lecturer_id=? AND unit_id=?");
    $stmt->bind_param('ii', $lecturer_id, $unit_id);
    $stmt->execute();
    $ok = $stmt->get_result()->fetch_assoc() !== null;
    $stmt->close();
    return $ok;
}

// NEW: get enrolled units for a student
function getStudentUnits($conn, $student_id) {
    $stmt = $conn->prepare("
        SELECT un.*, c.name AS course_name
        FROM unit_enrollments ue
        JOIN units un ON un.id = ue.unit_id
        JOIN courses c ON c.id = un.course_id
        WHERE ue.student_id = ?
        ORDER BY un.name
    ");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

// NEW: file upload handler (validates type, size, saves to uploads/)
function handleUpload($file, $allowedTypes = ['pdf','doc','docx','ppt','pptx','txt','jpg','jpeg','png'], $maxSize = 10485760) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) return [false, 'Upload error.'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedTypes, true)) return [false, 'File type not allowed.'];
    if ($file['size'] > $maxSize) return [false, 'File too large (max 10MB).'];
    
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    
    $newName = uniqid('f_', true) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) return [false, 'Could not save file.'];
    return [true, $newName];
}

function logAction($conn, $action, $entityType = null, $entityId = null, $details = null) {
    if (!isset($_SESSION['user_id'])) return;
    $uid = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'] ?? null;
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, user_role, action, entity_type, entity_id, details, ip_address) VALUES (?,?,?,?,?,?,?)");
    $stmt->bind_param('ississs', $uid, $role, $action, $entityType, $entityId, $details, $ip);
    $stmt->execute();
    $stmt->close();
}
?>