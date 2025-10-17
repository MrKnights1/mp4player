#!/bin/bash
# Start the PHP development server with URL routing

PORT=${1:-7000}

echo "Starting PHP server on port $PORT with URL router..."
echo "You can now access:"
echo "  - http://localhost:$PORT/"
echo "  - http://localhost:$PORT/admin (instead of /admin.php)"
echo "  - http://localhost:$PORT/videos (instead of /videos.php)"
echo ""
echo "Press Ctrl+C to stop the server"
echo ""

php -S 0.0.0.0:$PORT router.php
