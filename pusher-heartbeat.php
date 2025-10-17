<?php
require_once 'config.php';
require_once 'vendor/autoload.php';

use Pusher\Pusher;
use WhichBrowser\Parser;

// Get heartbeat data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['clientId'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing client data']);
    exit;
}

// Store client data in a JSON file (simple file-based storage)
$clientsFile = __DIR__ . '/clients.json';
$clients = [];

if (file_exists($clientsFile)) {
    $clients = json_decode(file_get_contents($clientsFile), true) ?: [];
}

// Check if this is a new client or existing one
$isNewClient = !isset($clients[$data['clientId']]);

// Parse user agent
$browserInfo = 'Unknown';
if (isset($data['userAgent'])) {
    $result = new Parser($data['userAgent']);
    $browserName = $result->browser->name ?? 'Unknown';
    $browserVersion = $result->browser->version->value ?? '';
    $osName = $result->os->name ?? '';

    $browserInfo = $browserName;
    if ($browserVersion) {
        $browserInfo .= ' ' . $browserVersion;
    }
    if ($osName) {
        $browserInfo .= ' on ' . $osName;
    }
}

// Update client data
$clients[$data['clientId']] = [
    'ip' => $data['ip'] ?? 'Unknown',
    'playing' => $data['playing'] ?? false,
    'lastSeen' => time(),
    'browser' => $browserInfo,
    // Only set connectedAt on first connection
    'connectedAt' => $isNewClient ? time() : $clients[$data['clientId']]['connectedAt']
];

// Remove stale clients (not seen in 120 seconds)
$clients = array_filter($clients, function($client) {
    return (time() - $client['lastSeen']) < 120;
});

// Save updated clients list
file_put_contents($clientsFile, json_encode($clients));

// Broadcast status update to admin panel
try {
    $pusher = new Pusher(
        PUSHER_KEY,
        PUSHER_SECRET,
        PUSHER_APP_ID,
        [
            'cluster' => PUSHER_CLUSTER,
            'useTLS' => true
        ]
    );

    // Prepare client list for admin
    $clientsList = [];
    foreach ($clients as $id => $client) {
        $clientsList[] = [
            'id' => $id,
            'ip' => $client['ip'],
            'browser' => $client['browser'] ?? 'Unknown',
            'playing' => $client['playing'],
            'duration' => time() - $client['connectedAt']
        ];
    }

    // Broadcast to admin channel
    $pusher->trigger('admin-channel', 'status-update', [
        'clients' => $clientsList
    ]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
