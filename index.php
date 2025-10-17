<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="86400">
    <title>24/7 MP4 Player</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #000;
            overflow: hidden;
            width: 100vw;
            height: 100vh;
        }

        #video-container {
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        #video-player {
            width: 100vw;
            height: 100vh;
            object-fit: cover;
            position: fixed;
            top: 0;
            left: 0;
        }

        #status {
            position: fixed;
            bottom: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 12px;
            z-index: 1000;
            display: none;
        }

        #status.show {
            display: block;
        }

        #ip-display {
            position: fixed;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            padding: 8px 12px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
            z-index: 1000;
        }

    </style>
</head>
<body>
    <div id="video-container">
        <video id="video-player" autoplay loop <?php echo ENABLE_SOUND ? '' : 'muted'; ?> playsinline>
            <source src="<?php echo VIDEO_FILE; ?>" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
    <div id="ip-display">Loading...</div>
    <div id="status"></div>

    <script>
        const video = document.getElementById('video-player');
        const videoContainer = document.getElementById('video-container');
        const statusDiv = document.getElementById('status');

        let currentVideoModified = null;
        let midnightCheckInterval = null;

        // Function to show status message
        function showStatus(message, duration = 3000) {
            statusDiv.textContent = message;
            statusDiv.classList.add('show');
            setTimeout(() => {
                statusDiv.classList.remove('show');
            }, duration);
        }

        // Function to enter fullscreen
        function enterFullscreen() {
            const elem = document.documentElement;

            if (elem.requestFullscreen) {
                elem.requestFullscreen().catch(err => {
                    console.log('Fullscreen error:', err);
                });
            } else if (elem.webkitRequestFullscreen) {
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) {
                elem.msRequestFullscreen();
            }
        }

        // Function to check video info
        async function checkVideoInfo() {
            try {
                const response = await fetch('api.php?action=video-info');
                const data = await response.json();

                if (data.error) {
                    console.error('Video error:', data.error);
                    return null;
                }

                return data;
            } catch (error) {
                console.error('Failed to check video info:', error);
                return null;
            }
        }

        // Function to check if it's midnight (within 1 minute window)
        function isMidnight() {
            const now = new Date();
            const hours = now.getHours();
            const minutes = now.getMinutes();

            return hours === 0 && minutes === 0;
        }

        // Function to check and reload video if changed
        async function checkAndReloadVideo() {
            const videoInfo = await checkVideoInfo();

            if (!videoInfo) return;

            // Store initial timestamp
            if (currentVideoModified === null) {
                currentVideoModified = videoInfo.lastModified;
                console.log('Initial video timestamp:', videoInfo.lastModifiedDate);
                return;
            }

            // Check if video has been modified
            if (videoInfo.lastModified !== currentVideoModified) {
                console.log('Video changed! Reloading...');
                showStatus('Video updated! Reloading...', 2000);

                // Wait a moment for status to show, then reload
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        }

        // Function to schedule midnight check
        function scheduleMidnightCheck() {
            // Check every minute if it's midnight
            setInterval(() => {
                if (isMidnight()) {
                    console.log('Midnight detected! Checking for video changes...');
                    checkAndReloadVideo();
                }
            }, 60000); // Check every 60 seconds
        }

        // WebSocket connection
        let ws = null;
        let isPlaying = false;
        let reconnectAttempts = 0;

        function connectWebSocket() {
            const wsUrl = 'ws://' + window.location.hostname + ':9090';
            console.log('[WS] Connecting to:', wsUrl);

            try {
                ws = new WebSocket(wsUrl);

                ws.onopen = function() {
                    console.log('[WS] Connected successfully');
                    reconnectAttempts = 0;

                    // Small delay to ensure connection is stable
                    setTimeout(() => {
                        // Register as video player
                        const registerMsg = JSON.stringify({
                            type: 'register',
                            client_type: 'player'
                        });
                        console.log('[WS] Sending registration:', registerMsg);
                        ws.send(registerMsg);

                        // Send initial playback status
                        setTimeout(() => {
                            sendPlaybackStatus();
                        }, 100);
                    }, 100);
                };

                ws.onmessage = function(event) {
                    console.log('[WS] Message received:', event.data);
                    try {
                        const data = JSON.parse(event.data);

                        if (data.type === 'video_updated') {
                            console.log('[WS] New video uploaded! Reloading page...');
                            showStatus('New video available! Updating...', 2000);
                            setTimeout(() => location.reload(), 2000);
                        } else if (data.type === 'refresh') {
                            console.log('[WS] Remote refresh requested! Reloading page...');
                            showStatus('Refreshing...', 1000);
                            setTimeout(() => location.reload(), 1000);
                        }
                    } catch (error) {
                        console.error('[WS] Message parse error:', error);
                    }
                };

                ws.onerror = function(error) {
                    console.error('[WS] Error:', error);
                };

                ws.onclose = function(event) {
                    console.log('[WS] Disconnected. Code:', event.code, 'Reason:', event.reason);
                    reconnectAttempts++;
                    const delay = Math.min(3000 * reconnectAttempts, 15000);
                    console.log('[WS] Reconnecting in', delay, 'ms...');
                    setTimeout(connectWebSocket, delay);
                };

            } catch (error) {
                console.error('[WS] Connection error:', error);
                setTimeout(connectWebSocket, 3000);
            }
        }

        function sendPlaybackStatus() {
            if (ws && ws.readyState === WebSocket.OPEN) {
                const msg = JSON.stringify({
                    type: 'playback_status',
                    playing: isPlaying
                });
                console.log('[WS] Sending playback status:', msg);
                ws.send(msg);
            } else {
                console.log('[WS] Cannot send - not connected. State:', ws ? ws.readyState : 'null');
            }
        }

        // Fetch and display IP address
        async function displayIP() {
            try {
                const response = await fetch('get-ip.php');
                const data = await response.json();
                if (data.success) {
                    document.getElementById('ip-display').textContent = 'TV IP: ' + data.ip;
                }
            } catch (error) {
                console.error('Failed to get IP:', error);
                document.getElementById('ip-display').textContent = 'IP: Unknown';
            }
        }

        // Initialize
        async function init() {
            // Display IP address
            displayIP();

            // Get initial video info
            await checkVideoInfo().then(info => {
                if (info) {
                    currentVideoModified = info.lastModified;
                    console.log('Video player initialized');
                    console.log('Video:', info.path);
                    console.log('Last modified:', info.lastModifiedDate);
                }
            });

            // Keyboard support for fullscreen (press F or Enter)
            document.addEventListener('keydown', (e) => {
                if (e.key === 'f' || e.key === 'F' || e.key === 'Enter') {
                    if (!document.fullscreenElement &&
                        !document.webkitFullscreenElement &&
                        !document.msFullscreenElement) {
                        enterFullscreen();
                    }
                }
            });

            // Start the midnight check scheduler
            scheduleMidnightCheck();

            // Connect to WebSocket
            connectWebSocket();

            // Ensure video plays
            video.play().catch(err => {
                console.log('Autoplay prevented:', err);
            });
        }

        // Start when page loads
        window.addEventListener('load', init);

        // Track video playback state
        video.addEventListener('play', () => {
            isPlaying = true;
            console.log('[Player] Video playing');
            sendPlaybackStatus();
        });

        video.addEventListener('playing', () => {
            isPlaying = true;
            sendPlaybackStatus();
        });

        video.addEventListener('pause', () => {
            // Don't report pause if tab is hidden (browser auto-pauses background tabs)
            if (!document.hidden) {
                isPlaying = false;
                console.log('[Player] Video paused');
                sendPlaybackStatus();
            } else {
                console.log('[Player] Video paused by browser (tab hidden) - ignoring');
            }
        });

        video.addEventListener('ended', () => {
            // Video has loop attribute, so this shouldn't happen
            isPlaying = false;
            sendPlaybackStatus();
        });

        // Handle visibility change
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                console.log('[Player] Tab visible - ensuring video plays');
                video.play().catch(err => console.log('Play error:', err));
                isPlaying = true;
                sendPlaybackStatus();
            } else {
                console.log('[Player] Tab hidden - keeping playing status');
            }
        });

        // Log any video errors
        video.addEventListener('error', (e) => {
            console.error('Video error:', e);
            showStatus('Video error! Check console.', 5000);
        });
    </script>
</body>
</html>
