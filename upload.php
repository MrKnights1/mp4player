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

// Check if file was uploaded
if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    $error_message = 'File upload error';

    if (isset($_FILES['video']['error'])) {
        switch ($_FILES['video']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $error_message = 'File is too large';
                break;
            case UPLOAD_ERR_PARTIAL:
                $error_message = 'File was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $error_message = 'No file was uploaded';
                break;
            default:
                $error_message = 'Unknown upload error';
        }
    }

    http_response_code(400);
    echo json_encode(['error' => $error_message]);
    exit;
}

$uploadedFile = $_FILES['video'];
$fileName = $uploadedFile['name'];
$fileTmpPath = $uploadedFile['tmp_name'];
$fileSize = $uploadedFile['size'];
$fileType = $uploadedFile['type'];

// Validate file type
$allowedMimeTypes = ['video/mp4'];
if (!in_array($fileType, $allowedMimeTypes)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file type. Only MP4 videos are allowed.']);
    exit;
}

// Validate file extension
$fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if ($fileExtension !== 'mp4') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file extension. Only .mp4 files are allowed.']);
    exit;
}

// Validate file size
$maxFileSize = MAX_UPLOAD_SIZE * 1024 * 1024; // Convert MB to bytes
if ($fileSize > $maxFileSize) {
    http_response_code(400);
    echo json_encode(['error' => 'File is too large. Maximum size: ' . MAX_UPLOAD_SIZE . 'MB']);
    exit;
}

// Move uploaded file to destination
$destinationPath = VIDEO_FILE;

// Create backup of existing video if it exists (keep only last 2 backups)
if (file_exists($destinationPath)) {
    $backup1Path = 'video.backup1.mp4';
    $backup2Path = 'video.backup2.mp4';

    // Delete oldest backup (backup2) if it exists
    if (file_exists($backup2Path)) {
        unlink($backup2Path);
    }

    // Move backup1 to backup2 if it exists
    if (file_exists($backup1Path)) {
        rename($backup1Path, $backup2Path);
    }

    // Move current video to backup1
    rename($destinationPath, $backup1Path);
}

// Move the uploaded file
if (move_uploaded_file($fileTmpPath, $destinationPath)) {
    // Set proper permissions
    chmod($destinationPath, 0644);

    // Trigger WebSocket notification
    require_once 'ws-notify.php';
    sendWebSocketNotification('video_uploaded', 'New video uploaded');

    echo json_encode([
        'success' => true,
        'message' => 'Video uploaded successfully',
        'filename' => basename($destinationPath),
        'size' => $fileSize,
        'uploaded_at' => date('Y-m-d H:i:s')
    ]);
} else {
    // Restore backup if move failed
    if (file_exists('video.backup1.mp4')) {
        rename('video.backup1.mp4', $destinationPath);
    }

    http_response_code(500);
    echo json_encode(['error' => 'Failed to save uploaded file']);
}
?>
