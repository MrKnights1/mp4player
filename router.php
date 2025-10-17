<?php
// Router for PHP built-in server
// This allows accessing pages without .php extension

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// List of routes that should work without .php extension
$routes = [
    '/admin' => 'admin.php',
    '/videos' => 'videos.php',
    '/index' => 'index.php',
];

// Check if the route exists in our map
if (isset($routes[$request_uri])) {
    $file = __DIR__ . '/' . $routes[$request_uri];
    if (file_exists($file)) {
        require $file;
        return true;
    }
}

// If requesting a directory, try index.php
if ($request_uri === '/' || $request_uri === '') {
    if (file_exists(__DIR__ . '/index.php')) {
        require __DIR__ . '/index.php';
        return true;
    }
}

// If the file exists as-is, serve it
$requested_file = __DIR__ . $request_uri;
if (file_exists($requested_file)) {
    // Let PHP serve static files or PHP files normally
    return false;
}

// Try adding .php extension
$php_file = __DIR__ . $request_uri . '.php';
if (file_exists($php_file)) {
    require $php_file;
    return true;
}

// File not found - return false to let PHP handle 404
return false;
?>
