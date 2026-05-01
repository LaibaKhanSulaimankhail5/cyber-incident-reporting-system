<?php
session_start();
header('Content-Type: application/json');

require_once '../config/db.php';
require_once '../config/ai_engine.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST required']);
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!$token || $token !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token']);
    exit;
}

$incident_id = intval($_POST['incident_id'] ?? 0);
if ($incident_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid incident ID']);
    exit;
}

try {
    if (($_SESSION['role'] ?? '') === 'admin') {
        $stmt = $pdo->prepare("SELECT * FROM incidents WHERE id = ?");
        $stmt->execute([$incident_id]);
    } else {
        $uid = $_SESSION['user_id'] ?? 0;
        $stmt = $pdo->prepare("SELECT * FROM incidents WHERE id = ? AND user_id = ?");
        $stmt->execute([$incident_id, $uid]);
    }
    $incident = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$incident) {
        echo json_encode(['status' => 'error', 'message' => 'Incident not found or access denied']);
        exit;
    }

    // AI Analysis generate karo using shared engine
    $analysis = runAIAnalysis(
        $incident['incident_type'],
        $incident['severity'],
        $incident['description'],
        $incident['title']
    );

    // Update DB
    $upd = $pdo->prepare("UPDATE incidents SET ai_analysis = ? WHERE id = ?");
    $upd->execute([$analysis['full_text'], $incident_id]);

    echo json_encode([
        'status'        => 'success',
        'incident_id'   => $incident_id,
        'risk_score'    => $analysis['risk_score'],
        'risk_label'    => $analysis['risk_label'],
        'analysis'      => $analysis['full_text'],
        'actions'       => $analysis['recommended_actions'],
        'mitre_tactic'  => $analysis['mitre_tactic']
    ]);

} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}