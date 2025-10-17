<?php
session_start();
require_once 'config.php';
require_once 'video-library.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin.php');
    exit;
}

// Get all videos from library
$all_videos = getAllVideos();
$active_video = getActiveVideo();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Library - MP4 Player</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e0e0e0;
        }

        h1 {
            color: #333;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-top: 5px;
            font-size: 14px;
        }

        .header-actions {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .search-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .search-input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .video-count {
            color: #666;
            font-size: 14px;
            white-space: nowrap;
        }

        .video-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }

        .video-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            transition: all 0.3s;
        }

        .video-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.15);
        }

        .video-card.active {
            border-color: #28a745;
            background: #f0fff4;
        }

        .video-header {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 12px;
        }

        .rename-btn {
            background: none;
            border: none;
            color: #667eea;
            cursor: pointer;
            padding: 4px 6px;
            border-radius: 3px;
            font-size: 16px;
            transition: all 0.2s;
            flex-shrink: 0;
        }

        .rename-btn:hover {
            background: #f0f0f0;
        }

        .video-name {
            font-weight: 600;
            color: #333;
            font-size: 16px;
            word-break: break-word;
            line-height: 1.4;
            flex: 1;
        }

        .video-name-input {
            flex: 1;
            padding: 4px 8px;
            border: 2px solid #667eea;
            border-radius: 4px;
            font-size: 16px;
            font-weight: 600;
        }

        .rename-actions {
            display: flex;
            gap: 4px;
            flex-shrink: 0;
        }

        .rename-save-btn, .rename-cancel-btn {
            padding: 4px 10px;
            border: none;
            border-radius: 3px;
            font-size: 12px;
            cursor: pointer;
            font-weight: 600;
        }

        .rename-save-btn {
            background: #28a745;
            color: white;
        }

        .rename-save-btn:hover {
            background: #218838;
        }

        .rename-cancel-btn {
            background: #6c757d;
            color: white;
        }

        .rename-cancel-btn:hover {
            background: #5a6268;
        }

        .video-meta {
            font-size: 13px;
            color: #666;
            margin-bottom: 8px;
        }

        .video-meta-item {
            display: inline-block;
            margin-right: 15px;
        }

        .active-badge {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 10px;
        }

        .video-actions {
            display: flex;
            gap: 10px;
            margin-top: 12px;
        }

        .btn-small {
            padding: 8px 16px;
            font-size: 13px;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .success {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .no-videos-found {
            text-align: center;
            padding: 60px 20px;
            color: #999;
            font-style: italic;
            grid-column: 1 / -1;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state h2 {
            color: #666;
            margin-bottom: 10px;
        }

        .upload-section {
            background: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }

        .upload-section h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 18px;
        }

        .upload-section p {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .upload-form {
            display: inline-block;
            text-align: left;
            max-width: 400px;
            width: 100%;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            color: #333;
            font-weight: 500;
            font-size: 14px;
            margin-bottom: 5px;
        }

        input[type="file"] {
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            width: 100%;
        }

        input[type="file"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 15px;
            display: none;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            width: 0%;
            transition: width 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <h1>Video Library</h1>
                <p class="subtitle">Manage all your videos</p>
            </div>
            <div class="header-actions">
                <a href="admin.php" class="btn btn-secondary">Back to Admin</a>
            </div>
        </div>

        <div id="status-message"></div>

        <div class="upload-section">
            <h3>Upload New Video</h3>
            <p>Upload an MP4 video to your library (Max: <?php echo MAX_UPLOAD_SIZE; ?>MB)</p>

            <form id="upload-form" class="upload-form" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="video-file">Select MP4 Video</label>
                    <input type="file" id="video-file" name="video" accept="video/mp4" required>
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Upload Video</button>

                <div class="progress-bar" id="progress-bar">
                    <div class="progress-fill" id="progress-fill">0%</div>
                </div>
            </form>
        </div>

        <?php if (count($all_videos) > 0): ?>
            <div class="search-bar">
                <input type="text" id="search-input" class="search-input" placeholder="Search videos by name..." autocomplete="off">
                <span class="video-count"><span id="video-count"><?php echo count($all_videos); ?></span> video(s)</span>
            </div>

            <div class="video-grid" id="video-grid">
                <?php foreach ($all_videos as $video): ?>
                    <div class="video-card <?php echo $video['active'] ? 'active' : ''; ?>" data-video-id="<?php echo htmlspecialchars($video['id']); ?>" data-video-name="<?php echo htmlspecialchars(strtolower($video['original_name'])); ?>">
                        <?php if ($video['active']): ?>
                            <div class="active-badge">CURRENTLY ACTIVE</div>
                        <?php endif; ?>

                        <div class="video-header">
                            <button class="rename-btn" title="Rename video">✎</button>
                            <div class="video-name">
                                <span class="video-name-text" data-original-name="<?php echo htmlspecialchars($video['original_name']); ?>"><?php echo htmlspecialchars($video['original_name']); ?></span>
                            </div>
                        </div>

                        <div class="video-meta">
                            <span class="video-meta-item">📦 <?php echo number_format($video['size'] / 1024 / 1024, 2); ?> MB</span>
                            <span class="video-meta-item">⏱ <?php echo formatDuration($video['duration'] ?? null); ?></span>
                            <span class="video-meta-item">📅 <?php echo htmlspecialchars($video['uploaded_at']); ?></span>
                        </div>

                        <div class="video-actions">
                            <?php if (!$video['active']): ?>
                                <button class="btn btn-success btn-small set-active-btn" data-video-id="<?php echo htmlspecialchars($video['id']); ?>">
                                    Set as Active
                                </button>
                                <button class="btn btn-danger btn-small delete-video-btn" data-video-id="<?php echo htmlspecialchars($video['id']); ?>">
                                    Delete
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary btn-small" disabled>
                                    Currently Playing
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="no-videos-found" id="no-videos-found" style="display: none;">
                    No videos found matching your search.
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <h2>No Videos Yet</h2>
                <p>Upload a video from the admin panel to get started.</p>
                <a href="admin.php" class="btn btn-primary" style="margin-top: 20px;">Go to Admin Panel</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        const statusMessage = document.getElementById('status-message');

        function showError(message) {
            statusMessage.innerHTML = '<div class="error">' + message + '</div>';
            setTimeout(() => {
                statusMessage.innerHTML = '';
            }, 5000);
        }

        function showSuccess(message) {
            statusMessage.innerHTML = '<div class="success">' + message + '</div>';
            setTimeout(() => {
                statusMessage.innerHTML = '';
            }, 3000);
        }

        // Upload functionality
        const uploadForm = document.getElementById('upload-form');
        const progressBar = document.getElementById('progress-bar');
        const progressFill = document.getElementById('progress-fill');
        const videoFile = document.getElementById('video-file');

        uploadForm.addEventListener('submit', async (e) => {
            e.preventDefault();

            const file = videoFile.files[0];
            if (!file) {
                showError('Please select a file');
                return;
            }

            // Check file type
            if (!file.type.includes('video/mp4')) {
                showError('Please select an MP4 video file');
                return;
            }

            // Check file size
            const maxSize = <?php echo MAX_UPLOAD_SIZE; ?> * 1024 * 1024;
            if (file.size > maxSize) {
                showError('File is too large. Maximum size: <?php echo MAX_UPLOAD_SIZE; ?>MB');
                return;
            }

            const formData = new FormData();
            formData.append('video', file);

            // Show progress bar
            progressBar.style.display = 'block';
            statusMessage.innerHTML = '';

            try {
                const xhr = new XMLHttpRequest();

                xhr.upload.addEventListener('progress', (e) => {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        progressFill.style.width = percentComplete + '%';
                        progressFill.textContent = Math.round(percentComplete) + '%';
                    }
                });

                xhr.addEventListener('load', () => {
                    if (xhr.status === 200) {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            showSuccess('Video uploaded successfully! Page will reload...');
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        } else {
                            showError(response.error || 'Upload failed');
                            progressBar.style.display = 'none';
                        }
                    } else {
                        showError('Upload failed. Server error.');
                        progressBar.style.display = 'none';
                    }
                });

                xhr.addEventListener('error', () => {
                    showError('Upload failed. Network error.');
                    progressBar.style.display = 'none';
                });

                xhr.open('POST', 'upload.php');
                xhr.send(formData);

            } catch (error) {
                showError('Upload failed: ' + error.message);
                progressBar.style.display = 'none';
            }
        });

        // Search functionality
        const searchInput = document.getElementById('search-input');
        const noVideosFound = document.getElementById('no-videos-found');
        const videoCount = document.getElementById('video-count');

        if (searchInput) {
            searchInput.addEventListener('input', () => {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const videoCards = document.querySelectorAll('.video-card');
                let visibleCount = 0;

                videoCards.forEach(card => {
                    const videoName = card.getAttribute('data-video-name');

                    if (searchTerm === '' || videoName.includes(searchTerm)) {
                        card.style.display = '';
                        visibleCount++;
                    } else {
                        card.style.display = 'none';
                    }
                });

                if (videoCount) {
                    videoCount.textContent = visibleCount;
                }

                if (noVideosFound) {
                    noVideosFound.style.display = visibleCount === 0 ? 'block' : 'none';
                }
            });
        }

        // Set Active button handlers
        document.querySelectorAll('.set-active-btn').forEach(button => {
            button.addEventListener('click', async () => {
                const videoId = button.getAttribute('data-video-id');

                if (!confirm('Set this video as the active video? Connected TVs will reload.')) {
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('video_id', videoId);

                    const response = await fetch('api.php?action=set-active-video', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showSuccess('Active video updated! Page will reload...');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showError(data.error || 'Failed to set active video');
                    }
                } catch (error) {
                    showError('Failed to set active video: ' + error.message);
                }
            });
        });

        // Delete button handlers
        document.querySelectorAll('.delete-video-btn').forEach(button => {
            button.addEventListener('click', async () => {
                const videoId = button.getAttribute('data-video-id');

                if (!confirm('Are you sure you want to delete this video? This action cannot be undone.')) {
                    return;
                }

                try {
                    const formData = new FormData();
                    formData.append('video_id', videoId);

                    const response = await fetch('api.php?action=delete-video', {
                        method: 'POST',
                        body: formData
                    });

                    const data = await response.json();

                    if (data.success) {
                        showSuccess('Video deleted! Page will reload...');
                        setTimeout(() => {
                            location.reload();
                        }, 1500);
                    } else {
                        showError(data.error || 'Failed to delete video');
                    }
                } catch (error) {
                    showError('Failed to delete video: ' + error.message);
                }
            });
        });

        // Rename functionality using event delegation
        document.addEventListener('click', (e) => {
            // Handle rename button click
            if (e.target.classList.contains('rename-btn')) {
                e.preventDefault();
                const videoCard = e.target.closest('.video-card');
                const videoHeader = e.target.closest('.video-header');
                const videoNameEl = videoHeader.querySelector('.video-name-text');
                const originalName = videoNameEl.getAttribute('data-original-name');
                const videoId = videoCard.getAttribute('data-video-id');

                // Create input field
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'video-name-input';
                input.value = originalName;

                // Create action buttons
                const saveBtn = document.createElement('button');
                saveBtn.className = 'rename-save-btn';
                saveBtn.textContent = '✓';
                saveBtn.title = 'Save';

                const cancelBtn = document.createElement('button');
                cancelBtn.className = 'rename-cancel-btn';
                cancelBtn.textContent = '✕';
                cancelBtn.title = 'Cancel';

                const actions = document.createElement('div');
                actions.className = 'rename-actions';
                actions.appendChild(saveBtn);
                actions.appendChild(cancelBtn);

                // Replace content
                videoHeader.innerHTML = '';
                videoHeader.appendChild(input);
                videoHeader.appendChild(actions);

                // Store data
                videoHeader.dataset.videoId = videoId;
                videoHeader.dataset.originalName = originalName;

                // Focus input
                input.focus();
                input.select();
            }

            // Handle save button
            if (e.target.classList.contains('rename-save-btn')) {
                e.preventDefault();
                const videoHeader = e.target.closest('.video-header');
                const input = videoHeader.querySelector('.video-name-input');
                const videoCard = videoHeader.closest('.video-card');
                const videoId = videoHeader.dataset.videoId;
                const originalName = videoHeader.dataset.originalName;
                const newName = input.value.trim();

                if (!newName) {
                    showError('Video name cannot be empty');
                    input.focus();
                    return;
                }

                if (newName === originalName) {
                    videoHeader.innerHTML = `<button class="rename-btn" title="Rename video">✎</button><div class="video-name"><span class="video-name-text" data-original-name="${originalName}">${originalName}</span></div>`;
                    return;
                }

                (async () => {
                    try {
                        const formData = new FormData();
                        formData.append('video_id', videoId);
                        formData.append('new_name', newName);

                        const response = await fetch('api.php?action=rename-video', {
                            method: 'POST',
                            body: formData
                        });

                        const data = await response.json();

                        if (data.success) {
                            videoCard.setAttribute('data-video-name', newName.toLowerCase());
                            videoHeader.innerHTML = `<button class="rename-btn" title="Rename video">✎</button><div class="video-name"><span class="video-name-text" data-original-name="${newName}">${newName}</span></div>`;
                            showSuccess('Video renamed successfully!');
                        } else {
                            showError(data.error || 'Failed to rename video');
                        }
                    } catch (error) {
                        showError('Failed to rename video: ' + error.message);
                    }
                })();
            }

            // Handle cancel button
            if (e.target.classList.contains('rename-cancel-btn')) {
                e.preventDefault();
                const videoHeader = e.target.closest('.video-header');
                const originalName = videoHeader.dataset.originalName;
                videoHeader.innerHTML = `<button class="rename-btn" title="Rename video">✎</button><div class="video-name"><span class="video-name-text" data-original-name="${originalName}">${originalName}</span></div>`;
            }
        });

        // Handle Enter/Escape keys
        document.addEventListener('keydown', (e) => {
            if (e.target.classList.contains('video-name-input')) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const saveBtn = e.target.parentElement.querySelector('.rename-save-btn');
                    if (saveBtn) saveBtn.click();
                } else if (e.key === 'Escape') {
                    const cancelBtn = e.target.parentElement.querySelector('.rename-cancel-btn');
                    if (cancelBtn) cancelBtn.click();
                }
            }
        });
    </script>
</body>
</html>
