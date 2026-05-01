<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';
require_once '../auth_middleware.php';

requireLogin();
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'CSRF failed']);
    exit;
}

$alert_id = (int)($_POST['alert_id'] ?? 0);

if ($alert_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Alert ID']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        UPDATE alerts 
        SET is_read = TRUE 
        WHERE id = ? 
        AND incident_id IN (
            SELECT id FROM incidents WHERE user_id = ?
        )
    ");
    $stmt->execute([$alert_id, $user_id]);

    echo json_encode(['status'=>'success']);

} catch (Exception $e) {
    echo json_encode(['status'=>'error']);
}
?>