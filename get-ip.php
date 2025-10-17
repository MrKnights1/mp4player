<?php
// Prevent errors from breaking JSON
ini_set('display_errors', 0);
error_reporting(0);

header('Content-Type: application/json');

// Get client IP address
$ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

// Check for forwarded IP (if behind proxy)
if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
} elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
    $ip = $_SERVER['HTTP_CLIENT_IP'];
}

echo json_encode([
    'success' => true,
    'ip' => $ip
]);
