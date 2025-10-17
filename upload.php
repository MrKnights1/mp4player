<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/upload-errors.log');

session_start();
require_once 'config.php';
require_once 'video-library.php';
require_once 'vendor/autoload.php';

use Pusher\Pusher;

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

// Create videos directory if it doesn't exist
if (!is_dir(VIDEO_DIRECTORY)) {
    mkdir(VIDEO_DIRECTORY, 0755, true);
}

// Generate unique filename with timestamp
$timestamp = time();
$sanitizedName = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($fileName, PATHINFO_FILENAME));
$uniqueFilename = $timestamp . '_' . $sanitizedName . '.mp4';
$destinationPath = VIDEO_DIRECTORY . $uniqueFilename;

// Move the uploaded file
if (move_uploaded_file($fileTmpPath, $destinationPath)) {
    // Set proper permissions
    chmod($destinationPath, 0644);

    // Add video to library
    if (!addVideoToLibrary($uniqueFilename, $fileName, $fileSize)) {
        // Failed to add to library, delete uploaded file
        unlink($destinationPath);
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add video to library']);
        exit;
    }

    // Trigger Pusher notification
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
            'message' => 'New video uploaded'
        ]);
    } catch (Exception $e) {
        // Log error but don't fail the upload
        error_log('Pusher notification failed: ' . $e->getMessage());
    }

    echo json_encode([
        'success' => true,
        'message' => 'Video uploaded successfully',
        'filename' => $uniqueFilename,
        'original_name' => $fileName,
        'size' => $fileSize,
        'uploaded_at' => date('Y-m-d H:i:s')
    ]);
} else {
    $error = error_get_last();
    error_log('Failed to move uploaded file: ' . print_r($error, true));
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save uploaded file']);
}
?>
