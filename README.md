# 24/7 Fullscreen MP4 Player

A simple PHP-based fullscreen video player that runs continuously and automatically updates the video at midnight when the file is changed.

## Features

- Fullscreen video playback
- Runs 24/7 with looping video
- Automatically checks for video file changes at midnight (00:00)
- **Admin panel for easy video management and upload**
- Configurable via .env file
- Works over HTTP
- No database required

## Requirements

- PHP 7.0 or higher
- A web browser with video support
- An MP4 video file

## Installation

1. Clone or download this project to your web server directory

2. Configure the `.env` file:

```bash
# Video file path (relative to this directory)
VIDEO_FILE=video.mp4

# Port to run the PHP server on
SERVER_PORT=8000

# Admin credentials
ADMIN_USERNAME=admin
ADMIN_PASSWORD=admin123

# Max upload size in MB
MAX_UPLOAD_SIZE=500

# Enable or disable sound (true/false)
ENABLE_SOUND=false
```

**IMPORTANT:** Change the default admin password before deploying!

3. Place your MP4 video file in the same directory and name it according to the `VIDEO_FILE` setting in `.env` (default: `video.mp4`)

## Usage

### Running with PHP Built-in Server

```bash
php -S 0.0.0.0:8000
```

Then open your browser and navigate to:

```
http://localhost:8000
```

Or from another device on the same network:

```
http://YOUR_SERVER_IP:8000
```

### Running with Apache/Nginx

Simply place the files in your web server document root and access via your configured domain or IP address.

### Running on TV/Kiosk Mode (Recommended for TV Displays)

For TV displays where you can't click on the screen, use the kiosk mode startup script:

```bash
./start-kiosk.sh
```

This will:
- Start the PHP server
- Automatically launch your browser in fullscreen kiosk mode
- Video will fill the entire screen with no browser UI visible

**Manual Browser Kiosk Mode:**

If the script doesn't work, you can manually start the browser in kiosk mode:

**Chrome/Chromium:**
```bash
chromium-browser --kiosk --noerrdialogs --disable-infobars http://localhost:7000
```

**Firefox:**
```bash
firefox --kiosk http://localhost:7000
```

**Keyboard Shortcuts:**
- Press `F` or `Enter` to toggle fullscreen (if you have a remote with keyboard)
- Press `ESC` to exit fullscreen

## Admin Panel

Access the admin panel to upload and manage videos:

```
http://localhost:8000/admin.php
```

**Default login credentials:**
- Username: `admin`
- Password: `admin`

### Admin Features

1. **View Current Video**: See details about the currently playing video (filename, size, last modified date)
2. **Preview Video**: Watch the current video before it goes live
3. **Player Settings**: Control video player options like sound on/off
4. **Upload New Video**: Upload a new MP4 video file (up to 500MB by default)
5. **Automatic Backup**: System keeps the last 2 video backups automatically

### Uploading a Video

1. Log in to the admin panel at `/admin.php`
2. Select your MP4 video file (max 500MB)
3. Click "Upload Video"
4. Wait for the upload to complete
5. The new video will be activated at midnight (00:00)

**Note:** The uploaded video replaces the existing `video.mp4` file. The system keeps the last 2 video backups as `video.backup1.mp4` (most recent) and `video.backup2.mp4` (second most recent). Older backups are automatically deleted.

## How It Works

1. **Fullscreen Mode**: The video always fills the entire viewport using CSS. For TV displays, launch the browser in kiosk mode using `./start-kiosk.sh` to hide all browser UI. Alternatively, press `F` or `Enter` on a keyboard/remote to toggle fullscreen.

2. **24/7 Playback**: The video is set to autoplay and loop continuously.

3. **Midnight Video Update**:

   - The system checks every minute if it's midnight (00:00)
   - At midnight, it queries the API to check if the video file has been modified
   - If the file timestamp has changed, the page automatically reloads to load the new video
   - This ensures the new video starts playing at midnight

4. **Changing the Video**:
   - Replace the video file (e.g., `video.mp4`) with your new video
   - Keep the same filename
   - The system will detect the change at the next midnight (00:00) and reload automatically

## File Structure

```
mp4player/
├── .env              # Configuration file
├── .htaccess         # Apache upload limits configuration
├── config.php        # Loads environment variables
├── api.php           # API endpoint for video info
├── index.php         # Main video player page
├── admin.php         # Admin panel for video management
├── upload.php        # Video upload handler
├── settings.php      # Settings save handler
├── php.ini           # PHP configuration for uploads
├── start.sh          # Basic startup script
├── start-kiosk.sh    # Kiosk mode startup script (for TVs)
├── video.mp4         # Your video file (place here)
└── README.md         # This file
```

## Configuration

Edit `.env` to customize:

- `VIDEO_FILE`: Path to your video file (default: `video.mp4`)
- `SERVER_PORT`: Port for PHP built-in server (default: `8000`)
- `ADMIN_USERNAME`: Admin panel username (default: `admin`)
- `ADMIN_PASSWORD`: Admin panel password (default: `admin`)
- `MAX_UPLOAD_SIZE`: Maximum upload size in MB (default: `500`)
- `ENABLE_SOUND`: Enable or disable video sound (default: `false`)

**Note:** The `ENABLE_SOUND` setting can also be toggled directly from the admin panel under "Player Settings".

## Troubleshooting

**Video won't play:**

- Ensure your video file exists and the path in `.env` is correct
- Check browser console for errors
- Try a different browser (Chrome/Firefox recommended)

**Fullscreen not working:**

- For TV displays, use `./start-kiosk.sh` to launch in kiosk mode
- Alternatively, press `F` or `Enter` key on keyboard/remote
- The video always fills the screen with CSS, but kiosk mode hides browser UI

**Video not updating at midnight:**

- Ensure the video file was actually modified (check file timestamp)
- Check browser console for errors
- The page must be open and running at midnight for the check to occur

**Video shows black screen:**

- Verify the video file is a valid MP4 format
- Try re-encoding the video with H.264 codec

## Browser Compatibility

Tested and working on:

- Chrome/Chromium
- Firefox
- Safari
- Edge

## License

Free to use and modify as needed.
