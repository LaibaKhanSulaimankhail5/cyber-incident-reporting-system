<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/ai_engine.php';
require_once __DIR__ . '/../config/update_tracker.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'POST required']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// CSRF validation
$token = $_POST['csrf_token'] ?? '';
if (!$token || $token !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token']);
    exit;
}

$user_id   = (int)$_SESSION['user_id'];
$title     = trim($_POST['title'] ?? '');
$type      = $_POST['incident_type'] ?? '';
$desc      = trim($_POST['description'] ?? '');
$severity  = $_POST['severity'] ?? 'medium';

$valid_types = ['phishing', 'malware', 'unauthorized_access', 'data_breach', 'other'];
$valid_sevs  = ['low', 'medium', 'high', 'critical'];

if (!$title || !$type || !$desc) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

if (strlen($title) > 255) {
    echo json_encode(['status' => 'error', 'message' => 'Title too long (max 255)']);
    exit;
}

if (!in_array($type, $valid_types) || !in_array($severity, $valid_sevs)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid classification data']);
    exit;
}


// Generate detailed AI Analysis using engine
$analysis_data = runAIAnalysis($type, $severity, $desc, $title);
$ai_analysis = $analysis_data['full_text'];

try {
    $pdo->beginTransaction();

    $s = $pdo->prepare("INSERT INTO incidents (user_id, title, incident_type, description, severity, ai_analysis) VALUES (?, ?, ?, ?, ?, ?)");
    $s->execute([$user_id, $title, $type, $desc, $severity, $ai_analysis]);
    $incident_id = $pdo->lastInsertId();

    // Create alert
    $alert_msg = "NEW INCIDENT: " . strtoupper($severity) . " - " . $title;
    $as = $pdo->prepare("INSERT INTO alerts (incident_id, alert_message, severity) VALUES (?, ?, ?)");
    $as->execute([$incident_id, $alert_msg, $severity]);

    $pdo->commit();
    triggerSystemUpdate();

    echo json_encode([
        'status'      => 'success',
        'message'     => 'Incident reported successfully',
        'incident_id' => $incident_id,
        'ai_analysis' => $ai_analysis
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}