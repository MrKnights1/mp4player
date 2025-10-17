<?php
// Video library management functions

require_once __DIR__ . '/vendor/autoload.php';

define('VIDEO_LIBRARY_FILE', __DIR__ . '/videos.json');
define('VIDEO_DIRECTORY', __DIR__ . '/videos/');

/**
 * Get video duration in seconds
 */
function getVideoDuration($videoPath) {
    try {
        $getID3 = new getID3;
        $fileInfo = $getID3->analyze($videoPath);

        if (isset($fileInfo['playtime_seconds'])) {
            return round($fileInfo['playtime_seconds']);
        }

        return null;
    } catch (Exception $e) {
        error_log('Failed to get video duration: ' . $e->getMessage());
        return null;
    }
}

/**
 * Format duration in seconds to human-readable format
 */
function formatDuration($seconds) {
    if ($seconds === null) {
        return 'Unknown';
    }

    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $secs = $seconds % 60;

    if ($hours > 0) {
        return sprintf('%d:%02d:%02d', $hours, $minutes, $secs);
    } else {
        return sprintf('%d:%02d', $minutes, $secs);
    }
}

/**
 * Load the video library from JSON file
 */
function loadVideoLibrary() {
    if (!file_exists(VIDEO_LIBRARY_FILE)) {
        return [
            'active_video' => null,
            'videos' => []
        ];
    }

    $content = file_get_contents(VIDEO_LIBRARY_FILE);
    return json_decode($content, true);
}

/**
 * Save the video library to JSON file
 */
function saveVideoLibrary($library) {
    $json = json_encode($library, JSON_PRETTY_PRINT);
    return file_put_contents(VIDEO_LIBRARY_FILE, $json) !== false;
}

/**
 * Get the active video info
 */
function getActiveVideo() {
    $library = loadVideoLibrary();
    if (!$library['active_video']) {
        return null;
    }

    $videoId = $library['active_video'];
    if (!isset($library['videos'][$videoId])) {
        return null;
    }

    return $library['videos'][$videoId];
}

/**
 * Get the active video path
 */
function getActiveVideoPath() {
    $activeVideo = getActiveVideo();
    if (!$activeVideo) {
        return null;
    }

    $path = VIDEO_DIRECTORY . $activeVideo['id'];
    return file_exists($path) ? $path : null;
}

/**
 * Get all videos in library
 */
function getAllVideos() {
    $library = loadVideoLibrary();

    // Sort by upload date descending
    $videos = $library['videos'];
    uasort($videos, function($a, $b) {
        return strtotime($b['uploaded_at']) - strtotime($a['uploaded_at']);
    });

    return $videos;
}

/**
 * Add a new video to library
 */
function addVideoToLibrary($filename, $originalName, $size) {
    $library = loadVideoLibrary();

    $videoId = $filename;

    // Get video duration
    $videoPath = VIDEO_DIRECTORY . $filename;
    $duration = getVideoDuration($videoPath);

    $library['videos'][$videoId] = [
        'id' => $videoId,
        'filename' => $filename,
        'original_name' => $originalName,
        'uploaded_at' => date('Y-m-d H:i:s'),
        'size' => $size,
        'duration' => $duration,
        'active' => false
    ];

    // If this is the first video, make it active
    if (count($library['videos']) === 1) {
        $library['active_video'] = $videoId;
        $library['videos'][$videoId]['active'] = true;

        // Create symlink for the active video
        $activeVideoPath = VIDEO_DIRECTORY . $videoId;
        $symlinkPath = __DIR__ . '/video.mp4';

        // Remove old symlink/file if exists
        if (file_exists($symlinkPath)) {
            if (is_link($symlinkPath)) {
                unlink($symlinkPath);
            }
        }

        // Create symlink
        symlink($activeVideoPath, $symlinkPath);
    }

    return saveVideoLibrary($library);
}

/**
 * Set a video as active
 */
function setActiveVideo($videoId) {
    $library = loadVideoLibrary();

    // Check if video exists
    if (!isset($library['videos'][$videoId])) {
        return false;
    }

    // Deactivate all videos
    foreach ($library['videos'] as $id => &$video) {
        $video['active'] = false;
    }

    // Activate the selected video
    $library['videos'][$videoId]['active'] = true;
    $library['active_video'] = $videoId;

    // Update the symlink for backward compatibility
    $activeVideoPath = VIDEO_DIRECTORY . $videoId;
    $symlinkPath = __DIR__ . '/video.mp4';

    // Remove old symlink/file if exists
    if (file_exists($symlinkPath)) {
        if (is_link($symlinkPath)) {
            unlink($symlinkPath);
        } else {
            // It's a regular file, rename it for backup
            rename($symlinkPath, __DIR__ . '/video.mp4.old');
        }
    }

    // Create new symlink
    symlink($activeVideoPath, $symlinkPath);

    return saveVideoLibrary($library);
}

/**
 * Delete a video from library
 */
function deleteVideo($videoId) {
    $library = loadVideoLibrary();

    // Check if video exists
    if (!isset($library['videos'][$videoId])) {
        return false;
    }

    // Don't allow deleting the active video
    if ($library['active_video'] === $videoId) {
        return false;
    }

    // Delete the file
    $videoPath = VIDEO_DIRECTORY . $videoId;
    if (file_exists($videoPath)) {
        unlink($videoPath);
    }

    // Remove from library
    unset($library['videos'][$videoId]);

    return saveVideoLibrary($library);
}

/**
 * Get video by ID
 */
function getVideoById($videoId) {
    $library = loadVideoLibrary();
    return $library['videos'][$videoId] ?? null;
}

/**
 * Rename a video
 */
function renameVideo($videoId, $newName) {
    $library = loadVideoLibrary();

    // Check if video exists
    if (!isset($library['videos'][$videoId])) {
        return false;
    }

    // Update the original name
    $library['videos'][$videoId]['original_name'] = $newName;

    return saveVideoLibrary($library);
}
?>
