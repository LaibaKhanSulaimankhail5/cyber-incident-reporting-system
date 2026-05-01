<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';
require_once '../auth_middleware.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {

    if ($role === 'admin') {
        $stmt = $pdo->query(
            "SELECT * FROM alerts WHERE is_read=FALSE ORDER BY created_at DESC LIMIT 20"
        );
    } else {
        $stmt = $pdo->prepare(
            "SELECT a.* FROM alerts a
             JOIN incidents i ON a.incident_id=i.id
             WHERE a.is_read=FALSE AND i.user_id=?
             ORDER BY a.created_at DESC LIMIT 20"
        );
        $stmt->execute([$user_id]);
    }

    $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'count' => count($alerts),
        'alerts' => $alerts
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Failed',
        'alerts' => [],
        'count' => 0
    ]);
}
?>