<?php
session_start();
require_once 'config.php';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $login_error = 'Invalid username or password';
    }
}

// Check if logged in
$is_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Get current video info
$current_video = VIDEO_FILE;
$video_exists = file_exists($current_video);
$video_info = null;

if ($video_exists) {
    $video_info = [
        'name' => basename($current_video),
        'size' => filesize($current_video),
        'modified' => filemtime($current_video),
        'modified_date' => date('Y-m-d H:i:s', filemtime($current_video))
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - MP4 Player</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        label {
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"],
        input[type="file"] {
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="file"]:focus {
            outline: none;
            border-color: #667eea;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
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

        .info-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }

        .info-box h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            font-size: 14px;
        }

        .info-label {
            color: #666;
            font-weight: 500;
        }

        .info-value {
            color: #333;
        }

        .upload-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }

        .header-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .progress-bar {
            width: 100%;
            height: 30px;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
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

        .video-preview {
            margin-top: 20px;
            text-align: center;
        }

        .video-preview video {
            max-width: 100%;
            border-radius: 5px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .links {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .settings-section {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 60px;
            height: 34px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #ccc;
            transition: .4s;
            border-radius: 34px;
        }

        .slider:before {
            position: absolute;
            content: "";
            height: 26px;
            width: 26px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked + .slider {
            background-color: #667eea;
        }

        input:checked + .slider:before {
            transform: translateX(26px);
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #e0e0e0;
        }

        .setting-item:last-child {
            border-bottom: none;
        }

        .setting-info {
            flex: 1;
        }

        .setting-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .setting-description {
            font-size: 13px;
            color: #666;
        }

        .client-card {
            background: white;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 12px;
            margin-bottom: 10px;
        }

        .client-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .client-ip {
            font-weight: 600;
            color: #333;
        }

        .status-playing {
            color: #28a745;
            font-weight: 600;
        }

        .status-paused {
            color: #ffc107;
            font-weight: 600;
        }

        .client-details {
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (!$is_logged_in): ?>
            <h1>Admin Login</h1>
            <p class="subtitle">Enter your credentials to access the admin panel</p>

            <?php if (isset($login_error)): ?>
                <div class="error"><?php echo htmlspecialchars($login_error); ?></div>
            <?php endif; ?>

            <form method="POST" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <button type="submit" name="login" class="btn btn-primary">Login</button>
            </form>

        <?php else: ?>
            <div class="header-actions">
                <div>
                    <h1>Admin Panel</h1>
                    <p class="subtitle">Manage your video player</p>
                </div>
                <a href="?logout=1" class="btn btn-secondary">Logout</a>
            </div>

            <?php if ($video_exists): ?>
                <div class="info-box">
                    <h3>Current Video</h3>
                    <div class="info-item">
                        <span class="info-label">File:</span>
                        <span class="info-value"><?php echo htmlspecialchars($video_info['name']); ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Size:</span>
                        <span class="info-value"><?php echo number_format($video_info['size'] / 1024 / 1024, 2); ?> MB</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Last Modified:</span>
                        <span class="info-value"><?php echo htmlspecialchars($video_info['modified_date']); ?></span>
                    </div>
                </div>

                <div class="video-preview">
                    <video controls width="100%">
                        <source src="<?php echo htmlspecialchars($current_video); ?>?t=<?php echo time(); ?>" type="video/mp4">
                    </video>
                </div>
            <?php else: ?>
                <div class="error">No video file found. Please upload a video.</div>
            <?php endif; ?>

            <div class="info-box" style="border-left-color: #28a745; margin-top: 20px;">
                <h3>TV Connection Status</h3>
                <div class="info-item">
                    <span class="info-label">WebSocket:</span>
                    <span class="info-value" id="ws-status">Disconnected</span>
                </div>
                <div class="info-item">
                    <span class="info-label">Connected TVs:</span>
                    <span class="info-value" id="tv-count">0</span>
                </div>
                <div id="tv-list" style="margin-top: 15px;"></div>
                <div style="margin-top: 15px; text-align: center;">
                    <button id="refresh-tvs-btn" class="btn btn-secondary" style="width: 100%;">Refresh All TVs</button>
                </div>
            </div>

            <div class="settings-section">
                <h3>Player Settings</h3>
                <p class="subtitle">Configure video player options</p>

                <div id="settings-status"></div>

                <div class="setting-item">
                    <div class="setting-info">
                        <div class="setting-title">Enable Sound</div>
                        <div class="setting-description">Turn video sound on or off. Changes take effect immediately.</div>
                    </div>
                    <label class="toggle-switch">
                        <input type="checkbox" id="enable-sound" <?php echo ENABLE_SOUND ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </div>
            </div>

            <div class="upload-section">
                <h3>Upload New Video</h3>

                <div id="upload-status"></div>

                <form id="upload-form" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="video-file">Select MP4 Video (Max: <?php echo MAX_UPLOAD_SIZE; ?>MB)</label>
                        <input type="file" id="video-file" name="video" accept="video/mp4" required>
                    </div>

                    <button type="submit" class="btn btn-primary">Upload Video</button>

                    <div class="progress-bar" id="progress-bar">
                        <div class="progress-fill" id="progress-fill">0%</div>
                    </div>
                </form>
            </div>

            <div class="links">
                <a href="index.php" class="btn btn-secondary" target="_blank">View Player</a>
            </div>

        <?php endif; ?>
    </div>

    <?php if ($is_logged_in): ?>
    <script>
        const uploadForm = document.getElementById('upload-form');
        const uploadStatus = document.getElementById('upload-status');
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
            uploadStatus.innerHTML = '';

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
                            showSuccess('Video uploaded successfully! It will be active at midnight (00:00).');
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

        function showError(message) {
            uploadStatus.innerHTML = '<div class="error">' + message + '</div>';
        }

        function showSuccess(message) {
            uploadStatus.innerHTML = '<div class="success">' + message + '</div>';
        }

        // Settings functionality
        const enableSoundToggle = document.getElementById('enable-sound');
        const settingsStatus = document.getElementById('settings-status');

        enableSoundToggle.addEventListener('change', async () => {
            const isEnabled = enableSoundToggle.checked;

            try {
                const formData = new FormData();
                formData.append('action', 'save');
                formData.append('enable_sound', isEnabled ? 'true' : 'false');

                const response = await fetch('settings.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    showSettingsSuccess('Settings saved successfully!');
                } else {
                    showSettingsError(data.error || 'Failed to save settings');
                    // Revert toggle on error
                    enableSoundToggle.checked = !isEnabled;
                }
            } catch (error) {
                showSettingsError('Failed to save settings: ' + error.message);
                // Revert toggle on error
                enableSoundToggle.checked = !isEnabled;
            }
        });

        function showSettingsError(message) {
            settingsStatus.innerHTML = '<div class="error">' + message + '</div>';
            setTimeout(() => {
                settingsStatus.innerHTML = '';
            }, 5000);
        }

        function showSettingsSuccess(message) {
            settingsStatus.innerHTML = '<div class="success">' + message + '</div>';
            setTimeout(() => {
                settingsStatus.innerHTML = '';
            }, 3000);
        }

        // WebSocket connection for TV status monitoring
        let ws = null;
        const wsStatus = document.getElementById('ws-status');
        const tvCount = document.getElementById('tv-count');
        const tvList = document.getElementById('tv-list');

        function connectWebSocket() {
            const wsUrl = 'ws://' + window.location.hostname + ':9090';
            console.log('Connecting to WebSocket:', wsUrl);

            ws = new WebSocket(wsUrl);

            ws.onopen = function() {
                console.log('WebSocket connected');
                wsStatus.textContent = 'Connected';
                wsStatus.style.color = '#28a745';

                // Register as admin
                ws.send(JSON.stringify({
                    type: 'register',
                    client_type: 'admin'
                }));
            };

            ws.onmessage = function(event) {
                console.log('[Admin WS] Raw message:', event.data);
                try {
                    const data = JSON.parse(event.data);
                    console.log('[Admin WS] Parsed message:', data);

                    if (data.type === 'status_update') {
                        console.log('[Admin WS] Status update received, clients:', data.clients);
                        updateTVStatus(data.clients);
                    }
                } catch (error) {
                    console.error('[Admin WS] Message parse error:', error);
                }
            };

            ws.onerror = function(error) {
                console.error('WebSocket error:', error);
                wsStatus.textContent = 'Error';
                wsStatus.style.color = '#dc3545';
            };

            ws.onclose = function() {
                console.log('WebSocket disconnected, reconnecting in 3 seconds...');
                wsStatus.textContent = 'Disconnected';
                wsStatus.style.color = '#6c757d';
                setTimeout(connectWebSocket, 3000);
            };
        }

        function updateTVStatus(clients) {
            tvCount.textContent = clients.length;

            if (clients.length === 0) {
                tvList.innerHTML = '<p style="text-align: center; color: #999; padding: 10px;">No TVs connected</p>';
                return;
            }

            let html = '';
            clients.forEach(client => {
                const statusClass = client.playing ? 'status-playing' : 'status-paused';
                const statusText = client.playing ? '▶ Playing' : '⏸ Paused';
                const duration = formatDuration(client.duration);

                html += `
                    <div class="client-card">
                        <div class="client-header">
                            <span class="client-ip">${client.ip}</span>
                            <span class="${statusClass}">${statusText}</span>
                        </div>
                        <div class="client-details">
                            Connected: ${duration}
                        </div>
                    </div>
                `;
            });

            tvList.innerHTML = html;
        }

        function formatDuration(seconds) {
            if (seconds < 60) return seconds + 's';
            const minutes = Math.floor(seconds / 60);
            if (minutes < 60) return minutes + 'm';
            const hours = Math.floor(minutes / 60);
            const remainingMinutes = minutes % 60;
            return hours + 'h ' + remainingMinutes + 'm';
        }

        // Refresh All TVs button
        const refreshTVsBtn = document.getElementById('refresh-tvs-btn');
        refreshTVsBtn.addEventListener('click', () => {
            if (ws && ws.readyState === WebSocket.OPEN) {
                console.log('[Admin] Sending refresh command to all TVs');
                ws.send(JSON.stringify({
                    type: 'refresh_clients'
                }));
                alert('Refresh command sent to all connected TVs!');
            } else {
                alert('WebSocket not connected. Please wait...');
            }
        });

        // Start WebSocket connection
        connectWebSocket();
    </script>
    <?php endif; ?>
</body>
</html>
