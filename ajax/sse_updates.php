<?php
/**
 * SERVER-SENT EVENTS (SSE) ENDPOINT
 * Broadcasts system-wide refresh triggers to all connected clients.
 */

session_start();
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');

require_once '../config/update_tracker.php';

// Disable time limit for the script
set_time_limit(0);

// Initial timestamp
$lastSeen = time();

while (true) {
    // Check if the system has been updated since we last checked
    $currentUpdate = getSystemLastUpdate();

    if ($currentUpdate > $lastSeen) {
        $lastSeen = $currentUpdate;
        echo "data: " . json_encode(['action' => 'refresh', 'time' => $lastSeen]) . "\n\n";
        
        // Flush the output buffer
        if (ob_get_level() > 0) ob_flush();
        flush();
    }

    // Sleep for 2 seconds to reduce CPU usage
    sleep(2);

    // Check if the connection is still alive (optional, PHP usually handles this)
    if (connection_aborted()) break;
}
