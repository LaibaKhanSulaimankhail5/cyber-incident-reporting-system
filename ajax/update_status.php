<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';
require_once '../auth_middleware.php';
require_once '../config/update_tracker.php';

requireLogin();
requireAdmin();
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'CSRF failed']);
    exit;
}


$incident_id = (int)($_POST['incident_id'] ?? 0);
$new_status = $_POST['status'] ?? '';
$admin_id = $_SESSION['user_id'];

$valid = ['open','investigating','resolved','closed'];

if (!in_array($new_status, $valid) || $incident_id <= 0) {
    echo json_encode(['status'=>'error','message'=>'Invalid data']);
    exit;
}

try {

    $pdo->beginTransaction();

    $pdo->prepare("UPDATE incidents SET status=? WHERE id=?")
        ->execute([$new_status, $incident_id]);

    $pdo->prepare("INSERT INTO investigation_log 
        (incident_id, admin_id, action, notes)
        VALUES (?,?,?,?)")
        ->execute([
            $incident_id,
            $admin_id,
            "Status changed to $new_status",
            $_POST['notes'] ?? ''
        ]);

    $pdo->commit();
    triggerSystemUpdate();

    echo json_encode([
        'status'=>'success',
        'message'=>'Updated'
    ]);

} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status'=>'error']);
}
?>