#!/bin/bash

# Load port from .env file
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# Use default port if not set
PORT=${SERVER_PORT:-8000}

echo "Starting MP4 Player server on port $PORT..."
echo "Launching browser in kiosk mode for TV display..."
echo ""

# Start PHP server in background
php -c php.ini -S 0.0.0.0:$PORT &
PHP_PID=$!

# Wait for server to start
sleep 2

# Detect browser and launch in kiosk mode
if command -v chromium-browser &> /dev/null; then
    chromium-browser --kiosk --noerrdialogs --disable-infobars --no-first-run --enable-features=OverlayScrollbar --start-fullscreen "http://localhost:$PORT" &
elif command -v google-chrome &> /dev/null; then
    google-chrome --kiosk --noerrdialogs --disable-infobars --no-first-run --start-fullscreen "http://localhost:$PORT" &
elif command -v chromium &> /dev/null; then
    chromium --kiosk --noerrdialogs --disable-infobars --no-first-run --start-fullscreen "http://localhost:$PORT" &
elif command -v firefox &> /dev/null; then
    firefox --kiosk "http://localhost:$PORT" &
else
    echo "No suitable browser found. Please open http://localhost:$PORT manually in fullscreen mode (press F11)"
fi

echo "Press Ctrl+C to stop the server"
wait $PHP_PID
