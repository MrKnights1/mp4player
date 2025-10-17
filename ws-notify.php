<?php
// Helper to send notifications via WebSocket
function sendWebSocketNotification($type, $message = '') {
    $host = '127.0.0.1';
    $port = 9090;

    try {
        $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (!$socket) return false;

        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 1, 'usec' => 0]);
        socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => 1, 'usec' => 0]);

        $connected = @socket_connect($socket, $host, $port);
        if (!$connected) {
            socket_close($socket);
            return false;
        }

        // Perform WebSocket handshake
        $key = base64_encode(random_bytes(16));
        $headers = "GET / HTTP/1.1\r\n";
        $headers .= "Host: {$host}:{$port}\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Key: {$key}\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n\r\n";

        socket_write($socket, $headers);

        // Read handshake response
        $response = socket_read($socket, 1024);
        if (strpos($response, '101 Switching Protocols') === false) {
            socket_close($socket);
            return false;
        }

        // Send message
        $payload = json_encode(['type' => $type, 'message' => $message]);
        $frame = encodeWebSocketFrame($payload);
        socket_write($socket, $frame);

        socket_close($socket);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

function encodeWebSocketFrame($message) {
    $length = strlen($message);
    $frame = chr(129); // Text frame with FIN bit

    if ($length <= 125) {
        $frame .= chr($length | 128); // Set mask bit
    } elseif ($length <= 65535) {
        $frame .= chr(126 | 128) . pack('n', $length);
    } else {
        $frame .= chr(127 | 128) . pack('J', $length);
    }

    // Add masking key
    $mask = pack('N', rand(1, 0x7FFFFFFF));
    $frame .= $mask;

    // Mask the payload
    for ($i = 0; $i < $length; $i++) {
        $frame .= $message[$i] ^ $mask[$i % 4];
    }

    return $frame;
}
