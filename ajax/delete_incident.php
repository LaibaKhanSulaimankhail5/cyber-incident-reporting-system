<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';
require_once '../auth_middleware.php';
require_once '../config/update_tracker.php';

requireLogin();
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status' => 'error', 'message' => 'CSRF failed']);
    exit;
}

$id = (int)($_POST['incident_id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid ID']);
    exit;
}

// Ownership check for employees
if ($_SESSION['role'] !== 'admin') {
    $check = $pdo->prepare("SELECT id FROM incidents WHERE id=? AND user_id=?");
    $check->execute([$id, $_SESSION['user_id']]);
    if (!$check->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized deletion']);
        exit;
    }
}


try {

    $pdo->beginTransaction();

    $pdo->prepare("DELETE FROM alerts WHERE incident_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM investigation_log WHERE incident_id=?")->execute([$id]);
    $pdo->prepare("DELETE FROM incidents WHERE id=?")->execute([$id]);

    $pdo->commit();
    triggerSystemUpdate();

    echo json_encode(['status'=>'success']);

} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status'=>'error']);
}
?>