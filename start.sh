#!/bin/bash

# Load port from .env file
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
fi

# Use default port if not set
PORT=${SERVER_PORT:-8000}

echo "Starting MP4 Player server on port $PORT..."
echo "Open your browser and navigate to http://localhost:$PORT"
echo "Press Ctrl+C to stop the server"
echo ""

php -d upload_max_filesize=500M -d post_max_size=500M -d max_execution_time=300 -d max_input_time=300 -d memory_limit=256M -S 0.0.0.0:$PORT
