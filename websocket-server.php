#!/usr/bin/env php
<?php
// Simple WebSocket server for video player
error_reporting(E_ALL);

$host = '0.0.0.0';
$port = 9090;

$clients = [];
$clientData = [];

// Create socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_bind($socket, $host, $port);
socket_listen($socket);
socket_set_nonblock($socket);

echo "WebSocket server started on ws://{$host}:{$port}\n";

while (true) {
    // Accept new connections
    $newSocket = @socket_accept($socket);
    if ($newSocket !== false) {
        socket_set_nonblock($newSocket);
        $clients[] = $newSocket;
        echo "New connection (" . count($clients) . " total)\n";
    }

    // Read from existing clients
    foreach ($clients as $key => $client) {
        $data = @socket_read($client, 4096, PHP_BINARY_READ);

        // Check for disconnection (false means error, empty string means no data available)
        if ($data === false) {
            $error = socket_last_error($client);
            socket_clear_error($client);

            // EAGAIN/EWOULDBLOCK means no data available (not an error on non-blocking sockets)
            if ($error == 11 || $error == 35) {
                // No data available, continue
                continue;
            }

            // Actual error - client disconnected
            echo "Client {$key} socket error: " . socket_strerror($error) . " (code: {$error})\n";
            socket_close($client);
            unset($clients[$key]);
            unset($clientData[$key]);
            echo "Client disconnected (" . count($clients) . " remaining)\n";
            broadcastStatus();
            continue;
        }

        // No data available yet
        if ($data === '') {
            continue;
        }

        // Check if this is a new connection (HTTP upgrade request)
        if (strpos($data, 'Sec-WebSocket-Key') !== false) {
            performHandshake($client, $data);
            $clientData[$key] = [
                'ip' => getClientIP($client),
                'connected_at' => time(),
                'playing' => false,
                'type' => 'unknown',
                'handshake_completed' => true
            ];
            echo "WebSocket handshake completed for client {$key}\n";
            broadcastStatus();
            // Don't try to decode this data as a frame - it's the HTTP handshake
            continue;
        }

        // Skip if handshake not yet completed
        if (!isset($clientData[$key]['handshake_completed'])) {
            continue;
        }

        // Decode WebSocket frame
        $decoded = decodeFrame($data);
        if ($decoded) {
            // Handle control frames
            if ($decoded === 'CLOSE') {
                echo "Client {$key} requested close\n";
                socket_close($client);
                unset($clients[$key]);
                unset($clientData[$key]);
                echo "Client disconnected (graceful close) - " . count($clients) . " remaining\n";
                broadcastStatus();
                continue;
            } elseif ($decoded === 'PING') {
                echo "Ping received from client {$key}, sending pong\n";
                // TODO: Send pong frame back
                continue;
            } elseif ($decoded === 'PONG') {
                echo "Pong received from client {$key}\n";
                continue;
            }

            // Handle text frames (JSON messages)
            echo "Received data from client {$key}: {$decoded}\n";
            $message = json_decode($decoded, true);
            if ($message) {
                echo "Parsed message: " . json_encode($message) . "\n";
                handleMessage($key, $message);
            } else {
                echo "Failed to parse JSON from client {$key}\n";
            }
        } else {
            echo "Failed to decode frame from client {$key}\n";
        }
    }

    // Broadcast status every 5 seconds
    static $lastBroadcast = 0;
    if (time() - $lastBroadcast > 5) {
        broadcastStatus();
        $lastBroadcast = time();
    }

    usleep(100000); // 100ms
}

function performHandshake($client, $headers) {
    preg_match('/Sec-WebSocket-Key: (.*)\\r\\n/', $headers, $matches);
    if (empty($matches[1])) return false;

    $key = trim($matches[1]);
    $acceptKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));

    $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                "Upgrade: websocket\r\n" .
                "Connection: Upgrade\r\n" .
                "Sec-WebSocket-Accept: {$acceptKey}\r\n\r\n";

    socket_write($client, $response);
    return true;
}

function decodeFrame($data) {
    if (strlen($data) < 2) {
        return false;
    }

    $opcode = ord($data[0]) & 0x0F;
    $length = ord($data[1]) & 127;
    $masked = ord($data[1]) & 128;

    // Handle control frames
    if ($opcode === 0x8) {
        // Close frame
        echo "Client sent close frame\n";
        return 'CLOSE';
    } elseif ($opcode === 0x9) {
        // Ping frame
        return 'PING';
    } elseif ($opcode === 0xA) {
        // Pong frame
        return 'PONG';
    }

    // Only process text frames (opcode 0x1)
    if ($opcode !== 0x1) {
        return false;
    }

    if (!$masked) {
        echo "Frame not masked (client frames must be masked)\n";
        return false;
    }

    $headerSize = 2;
    $payloadLength = $length;

    if ($length == 126) {
        $headerSize = 4;
        if (strlen($data) < $headerSize + 4) return false;
        $payloadLength = unpack('n', substr($data, 2, 2))[1];
        $masks = substr($data, 4, 4);
        $payload = substr($data, 8, $payloadLength);  // Extract ONLY the payload length
    } elseif ($length == 127) {
        $headerSize = 10;
        if (strlen($data) < $headerSize + 4) return false;
        $payloadLength = unpack('J', substr($data, 2, 8))[1];
        $masks = substr($data, 10, 4);
        $payload = substr($data, 14, $payloadLength);  // Extract ONLY the payload length
    } else {
        if (strlen($data) < $headerSize + 4 + $length) return false;
        $masks = substr($data, 2, 4);
        $payload = substr($data, 6, $length);  // Extract ONLY the payload length
    }

    $text = '';
    for ($i = 0; $i < $payloadLength; $i++) {
        $text .= $payload[$i] ^ $masks[$i % 4];
    }

    return $text;
}

function encodeFrame($message) {
    $length = strlen($message);
    $frame = chr(129); // Text frame

    if ($length <= 125) {
        $frame .= chr($length);
    } elseif ($length <= 65535) {
        $frame .= chr(126) . pack('n', $length);
    } else {
        $frame .= chr(127) . pack('J', $length);
    }

    return $frame . $message;
}

function handleMessage($clientKey, $message) {
    global $clientData, $clients;

    $type = $message['type'] ?? '';

    switch ($type) {
        case 'register':
            $clientData[$clientKey]['type'] = $message['client_type'] ?? 'unknown';
            echo "Client {$clientKey} registered as {$clientData[$clientKey]['type']}\n";
            broadcastStatus();
            break;

        case 'playback_status':
            $clientData[$clientKey]['playing'] = $message['playing'] ?? false;
            broadcastStatus();
            break;

        case 'video_uploaded':
        case 'video_updated':
            // Broadcast to all video players
            broadcast(['type' => 'video_updated', 'message' => 'New video uploaded'], 'player');
            break;

        case 'refresh_clients':
            echo "Admin requested client refresh\n";
            // Broadcast refresh command to all video players
            broadcast(['type' => 'refresh', 'message' => 'Refresh requested by admin'], 'player');
            break;
    }
}

function broadcastStatus() {
    global $clientData;

    $players = array_filter($clientData, fn($c) => ($c['type'] ?? '') === 'player');

    $clientsList = array_values(array_map(function($data) {
        return [
            'ip' => $data['ip'],
            'playing' => $data['playing'],
            'connected_at' => $data['connected_at'],
            'duration' => time() - $data['connected_at']
        ];
    }, $players));

    $status = [
        'type' => 'status_update',
        'clients' => $clientsList
    ];

    echo "Broadcasting status to admins: " . count($clientsList) . " players\n";
    broadcast($status, 'admin');
}

function broadcast($message, $targetType = null) {
    global $clients, $clientData;

    $encoded = encodeFrame(json_encode($message));
    $sent = 0;

    foreach ($clients as $key => $client) {
        if ($targetType && isset($clientData[$key]) && $clientData[$key]['type'] !== $targetType) {
            continue;
        }
        $result = @socket_write($client, $encoded);
        if ($result !== false) {
            $sent++;
        }
    }

    if ($targetType) {
        echo "Broadcast to {$targetType} clients: {$sent} messages sent\n";
    }
}

function getClientIP($socket) {
    socket_getpeername($socket, $ip);
    return $ip;
}
