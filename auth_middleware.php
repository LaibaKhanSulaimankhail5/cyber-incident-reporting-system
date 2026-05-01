<?php
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['status'=>'error','message'=>'Not logged in']);
        exit;
    }
}

function requireAdmin() {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        echo json_encode(['status'=>'error','message'=>'Unauthorized']);
        exit;
    }
}

function checkCSRF() {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        echo json_encode(['status'=>'error','message'=>'CSRF failed']);
        exit;
    }
}
?>