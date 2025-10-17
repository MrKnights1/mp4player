<?php
require_once 'config.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'video-info':
        $videoPath = VIDEO_FILE;

        if (!file_exists($videoPath)) {
            echo json_encode([
                'error' => 'Video file not found',
                'path' => $videoPath
            ]);
            http_response_code(404);
            exit;
        }

        $lastModified = filemtime($videoPath);

        echo json_encode([
            'path' => $videoPath,
            'lastModified' => $lastModified,
            'lastModifiedDate' => date('Y-m-d H:i:s', $lastModified),
            'url' => $videoPath . '?t=' . $lastModified
        ]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        http_response_code(400);
        break;
}
?>
