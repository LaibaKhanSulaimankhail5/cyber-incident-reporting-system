<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status'=>'error','message'=>'Not logged in']);
    exit;
}

require_once '../config/db.php';

$user_id = (int)$_SESSION['user_id'];
$role    = $_SESSION['role'];

try {
    if ($role === 'admin') {
        $stmt = $pdo->query(
            "SELECT
                COUNT(*) AS t,
                SUM(status='open') AS open_count,
                SUM(status='investigating') AS investigating_count,
                SUM(status='resolved') AS resolved_count,
                SUM(status='closed') AS closed_count,
                SUM(severity='critical') AS critical_count,
                SUM(severity='high') AS high_count
             FROM incidents"
        );
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $alert_count = (int)$pdo->query("SELECT COUNT(*) FROM alerts WHERE is_read=FALSE")->fetchColumn();
    } else {
        $stmt = $pdo->prepare(
            "SELECT
                COUNT(*) AS t,
                SUM(status='open') AS open_count,
                SUM(status='investigating') AS investigating_count,
                SUM(status='resolved') AS resolved_count,
                SUM(status='closed') AS closed_count,
                SUM(severity='critical') AS critical_count,
                SUM(severity='high') AS high_count
             FROM incidents WHERE user_id=?"
        );
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        $as = $pdo->prepare(
            "SELECT COUNT(*) FROM alerts a
             JOIN incidents i ON a.incident_id=i.id
             WHERE a.is_read=FALSE AND i.user_id=?"
        );
        $as->execute([$user_id]);
        $alert_count = (int)$as->fetchColumn();


    }

    echo json_encode([
        'status'        => 'success',
        'total'         => (int)($stats['t'] ?? 0),
        'open'          => (int)($stats['open_count'] ?? 0),
        'investigating' => (int)($stats['investigating_count'] ?? 0),
        'resolved'      => (int)($stats['resolved_count'] ?? 0),
        'closed'        => (int)($stats['closed_count'] ?? 0),
        'critical'      => (int)($stats['critical_count'] ?? 0),
        'high'          => (int)($stats['high_count'] ?? 0),
        'alerts'        => $alert_count
    ]);

} catch (PDOException $e) {
    error_log('get_stats error: ' . $e->getMessage());
    echo json_encode(['status'=>'error','message'=>'Query failed']);
}
