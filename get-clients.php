<?php
// Get current client status from clients.json
header('Content-Type: application/json');

$clientsFile = __DIR__ . '/clients.json';
$clients = [];

if (file_exists($clientsFile)) {
    $clients = json_decode(file_get_contents($clientsFile), true) ?: [];
}

// Remove stale clients (not seen in 120 seconds)
$clients = array_filter($clients, function($client) {
    return (time() - $client['lastSeen']) < 120;
});

// Save cleaned list back
file_put_contents($clientsFile, json_encode($clients));

// Prepare client list
$clientsList = [];
foreach ($clients as $id => $client) {
    $clientsList[] = [
        'id' => $id,
        'ip' => $client['ip'],
        'browser' => $client['browser'] ?? 'Unknown',
        'playing' => $client['playing'],
        'duration' => time() - $client['connectedAt'],
        'lastSeen' => $client['lastSeen']
    ];
}

echo json_encode([
    'success' => true,
    'clients' => $clientsList
]);
?>
