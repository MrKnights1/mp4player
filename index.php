<?php
require_once 'config.php';
require_once 'video-library.php';

// Get the active video from library
$active_video = getActiveVideo();
$video_url = 'video.mp4'; // Fallback to symlink

if ($active_video) {
    // Use the direct path from videos directory with cache busting
    $video_url = 'videos/' . $active_video['id'] . '?t=' . time();
}
?>
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


    </style>
</head>
<body>
    <div id="video-container">
        <video id="video-player" autoplay loop <?php echo ENABLE_SOUND ? '' : 'muted'; ?> playsinline>
            <source src="<?php echo $video_url; ?>" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
    <div id="status"></div>

    <!-- Pusher JS Library -->
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>

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

        // Pusher connection
        let pusher = null;
        let isPlaying = false;
        let heartbeatInterval = null;

        // Get or create persistent client ID
        let clientId = localStorage.getItem('tv_client_id');
        if (!clientId) {
            clientId = 'player_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('tv_client_id', clientId);
        }
        console.log('[Player] Client ID:', clientId);

        function initPusher() {
            console.log('[Pusher] Initializing...');

            // Initialize Pusher
            pusher = new Pusher('<?php echo PUSHER_KEY; ?>', {
                cluster: '<?php echo PUSHER_CLUSTER; ?>',
                forceTLS: true
            });

            // Subscribe to video channel
            const channel = pusher.subscribe('video-channel');

            channel.bind('pusher:subscription_succeeded', function() {
                console.log('[Pusher] Connected successfully');

                // Send initial heartbeat
                sendHeartbeat();

                // Start heartbeat interval (every 60 seconds)
                heartbeatInterval = setInterval(sendHeartbeat, 60000);
            });

            channel.bind('video-updated', function(data) {
                console.log('[Pusher] New video uploaded!', data);
                showStatus('New video available! Updating...', 2000);
                setTimeout(() => location.reload(), 2000);
            });

            channel.bind('refresh', function(data) {
                console.log('[Pusher] Remote refresh requested!', data);
                showStatus('Refreshing...', 1000);
                setTimeout(() => location.reload(), 1000);
            });

            pusher.connection.bind('error', function(err) {
                console.error('[Pusher] Connection error:', err);
            });
        }

        async function sendHeartbeat() {
            try {
                const response = await fetch('get-ip.php');
                const data = await response.json();
                const clientIp = data.success ? data.ip : 'Unknown';

                await fetch('pusher-heartbeat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        clientId: clientId,
                        ip: clientIp,
                        playing: isPlaying,
                        timestamp: Date.now(),
                        userAgent: navigator.userAgent
                    })
                });

                console.log('[Pusher] Heartbeat sent - playing:', isPlaying);
            } catch (error) {
                console.error('[Pusher] Heartbeat error:', error);
            }
        }

        function sendPlaybackStatus() {
            // Send status immediately
            sendHeartbeat();
        }


        // Initialize
        async function init() {
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

            // Connect to Pusher
            initPusher();

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
