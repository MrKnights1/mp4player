<?php
// Load environment variables from .env file
function loadEnv($path = '.env') {
    if (!file_exists($path)) {
        return [];
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $env = [];

    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            $env[$key] = $value;
        }
    }

    return $env;
}

// Load environment variables
$env = loadEnv();

// Set default values
define('VIDEO_FILE', $env['VIDEO_FILE'] ?? 'video.mp4');
define('SERVER_PORT', $env['SERVER_PORT'] ?? '8000');
define('ADMIN_USERNAME', $env['ADMIN_USERNAME'] ?? 'admin');
define('ADMIN_PASSWORD', $env['ADMIN_PASSWORD'] ?? 'admin123');
define('MAX_UPLOAD_SIZE', $env['MAX_UPLOAD_SIZE'] ?? '500');
define('ENABLE_SOUND', strtolower($env['ENABLE_SOUND'] ?? 'false') === 'true');
?>
