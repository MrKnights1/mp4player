<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'get':
        // Get current settings
        echo json_encode([
            'success' => true,
            'settings' => [
                'enable_sound' => ENABLE_SOUND,
                'video_file' => VIDEO_FILE,
                'max_upload_size' => MAX_UPLOAD_SIZE
            ]
        ]);
        break;

    case 'save':
        // Save settings to .env file
        $enableSound = isset($_POST['enable_sound']) && $_POST['enable_sound'] === 'true';

        // Read current .env file
        $envPath = '.env';
        if (!file_exists($envPath)) {
            http_response_code(500);
            echo json_encode(['error' => '.env file not found']);
            exit;
        }

        $envContent = file_get_contents($envPath);
        if ($envContent === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to read .env file']);
            exit;
        }

        // Update ENABLE_SOUND value
        $newValue = $enableSound ? 'true' : 'false';
        $envContent = preg_replace(
            '/^ENABLE_SOUND=.*/m',
            'ENABLE_SOUND=' . $newValue,
            $envContent
        );

        // Write updated content back to .env file
        if (file_put_contents($envPath, $envContent) === false) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to write .env file']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'message' => 'Settings saved successfully',
            'settings' => [
                'enable_sound' => $enableSound
            ]
        ]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>
