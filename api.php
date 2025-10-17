<?php
session_start();
require_once 'config.php';
require_once 'video-library.php';
require_once 'vendor/autoload.php';

use Pusher\Pusher;

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'video-info':
        $videoPath = getActiveVideoPath();

        if (!$videoPath || !file_exists($videoPath)) {
            // Fallback to old VIDEO_FILE for backward compatibility
            $videoPath = VIDEO_FILE;
            if (!file_exists($videoPath)) {
                echo json_encode([
                    'error' => 'Video file not found',
                    'path' => $videoPath
                ]);
                http_response_code(404);
                exit;
            }
        }

        $lastModified = filemtime($videoPath);

        echo json_encode([
            'path' => $videoPath,
            'lastModified' => $lastModified,
            'lastModifiedDate' => date('Y-m-d H:i:s', $lastModified),
            'url' => $videoPath . '?t=' . $lastModified
        ]);
        break;

    case 'list-videos':
        $videos = getAllVideos();
        echo json_encode([
            'success' => true,
            'videos' => array_values($videos),
            'active_video' => getActiveVideo()
        ]);
        break;

    case 'set-active-video':
        // Check if user is logged in
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $videoId = $_POST['video_id'] ?? '';
        if (!$videoId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing video_id']);
            exit;
        }

        if (setActiveVideo($videoId)) {
            // Trigger Pusher notification to all connected players
            try {
                $pusher = new Pusher(
                    PUSHER_KEY,
                    PUSHER_SECRET,
                    PUSHER_APP_ID,
                    [
                        'cluster' => PUSHER_CLUSTER,
                        'useTLS' => true
                    ]
                );

                $pusher->trigger('video-channel', 'video-updated', [
                    'message' => 'Active video changed',
                    'video_id' => $videoId
                ]);
            } catch (Exception $e) {
                error_log('Pusher notification failed: ' . $e->getMessage());
            }

            echo json_encode([
                'success' => true,
                'message' => 'Active video updated',
                'video_id' => $videoId
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Video not found']);
        }
        break;

    case 'delete-video':
        // Check if user is logged in
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $videoId = $_POST['video_id'] ?? '';
        if (!$videoId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing video_id']);
            exit;
        }

        if (deleteVideo($videoId)) {
            echo json_encode([
                'success' => true,
                'message' => 'Video deleted'
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Cannot delete active video or video not found']);
        }
        break;

    case 'rename-video':
        // Check if user is logged in
        if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
            http_response_code(401);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }

        $videoId = $_POST['video_id'] ?? '';
        $newName = $_POST['new_name'] ?? '';

        if (!$videoId || !$newName) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing video_id or new_name']);
            exit;
        }

        // Sanitize the new name
        $newName = trim($newName);
        if (empty($newName)) {
            http_response_code(400);
            echo json_encode(['error' => 'Video name cannot be empty']);
            exit;
        }

        if (renameVideo($videoId, $newName)) {
            echo json_encode([
                'success' => true,
                'message' => 'Video renamed successfully',
                'new_name' => $newName
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Video not found']);
        }
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        http_response_code(400);
        break;
}
?>
