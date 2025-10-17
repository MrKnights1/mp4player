<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use Pusher\Pusher;

// Initialize Pusher
$pusher = new Pusher(
    PUSHER_KEY,
    PUSHER_SECRET,
    PUSHER_APP_ID,
    [
        'cluster' => PUSHER_CLUSTER,
        'useTLS' => true
    ]
);

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['event'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing event type']);
    exit;
}

$event = $data['event'];
$payload = $data['data'] ?? [];

try {
    switch ($event) {
        case 'video_uploaded':
            // Notify all players that a new video is available
            $pusher->trigger('video-channel', 'video-updated', [
                'message' => 'New video uploaded'
            ]);
            break;

        case 'refresh_clients':
            // Trigger refresh for all players
            $pusher->trigger('video-channel', 'refresh', [
                'message' => 'Refresh requested by admin'
            ]);
            break;

        case 'playback_status':
            // Update playback status for a specific client
            $pusher->trigger('admin-channel', 'status-update', $payload);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown event type']);
            exit;
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
