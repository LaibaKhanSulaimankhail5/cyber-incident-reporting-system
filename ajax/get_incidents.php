<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';
require_once '../auth_middleware.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

$status   = $_GET['status'] ?? 'all';
$type     = $_GET['type'] ?? 'all';
$severity = $_GET['severity'] ?? 'all';

$v_status = ['all','open','investigating','resolved','closed'];
$v_type   = ['all','phishing','malware','unauthorized_access','data_breach','other'];
$v_sev    = ['all','low','medium','high','critical'];

if (!in_array($status, $v_status) || !in_array($type, $v_type) || !in_array($severity, $v_sev)) {
    echo json_encode(['status'=>'error', 'message'=>'Invalid filter']);
    exit;
}


try {

    if ($role === 'admin') {
        $sql = "SELECT i.*, u.name as reporter_name 
                FROM incidents i 
                LEFT JOIN users u ON i.user_id = u.id 
                WHERE 1=1";
        $params = [];

    } else {
        $sql = "SELECT i.*, u.name as reporter_name 
                FROM incidents i 
                LEFT JOIN users u ON i.user_id = u.id 
                WHERE i.user_id = ?";
        $params = [$user_id];
    }

    if ($status !== 'all') {
        $sql .= " AND status = ?";
        $params[] = $status;
    }

    if ($type !== 'all') {
        $sql .= " AND incident_type = ?";
        $params[] = $type;
    }

    if ($severity !== 'all') {
        $sql .= " AND severity = ?";
        $params[] = $severity;
    }

    $sql .= " ORDER BY i.reported_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status'=>'success',
        'incidents'=>$data
    ]);

} catch(Exception $e) {
    echo json_encode(['status'=>'error','incidents'=>[]]);
}