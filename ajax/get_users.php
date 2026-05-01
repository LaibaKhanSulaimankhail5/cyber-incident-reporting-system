<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';
require_once '../auth_middleware.php';

requireLogin();
requireAdmin();

try {

    $stmt = $pdo->query("SELECT id,name,email,role,created_at FROM users ORDER BY created_at DESC");

    echo json_encode([
        'status'=>'success',
        'users'=>$stmt->fetchAll(PDO::FETCH_ASSOC)
    ]);

} catch(Exception $e) {
    echo json_encode(['status'=>'error','users'=>[]]);
}
?>