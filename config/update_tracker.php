<?php
/**
 * Utility to track the last modification time of any incident or alert.
 * Used by SSE to detect changes without heavy DB polling.
 */

function getSystemLastUpdate() {
    $file = __DIR__ . '/last_change.txt';
    if (!file_exists($file)) {
        touch($file);
    }
    return filemtime($file);
}

function triggerSystemUpdate() {
    $file = __DIR__ . '/last_change.txt';
    touch($file);
}
