<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';
require_once '../auth_middleware.php';

requireLogin();
requireAdmin();
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['status'=>'error','message'=>'CSRF failed']);
    exit;
}
require_once '../config/update_tracker.php';


$id = (int)($_POST['user_id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['status'=>'error', 'message'=>'Invalid ID']);
    exit;
}

if ($id === (int)$_SESSION['user_id']) {
    echo json_encode(['status'=>'error', 'message'=>'Cannot delete self']);
    exit;
}


try {

    $pdo->beginTransaction();

    $pdo->prepare("UPDATE incidents SET user_id=1 WHERE user_id=?")
        ->execute([$id]);

    $pdo->prepare("DELETE FROM users WHERE id=?")
        ->execute([$id]);

    $pdo->commit();
    triggerSystemUpdate();

    echo json_encode(['status'=>'success']);

} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status'=>'error']);
}
?>