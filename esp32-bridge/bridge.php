<?php

require __DIR__ . '/vendor/autoload.php';

use React\EventLoop\Loop;
use React\Socket\ConnectionInterface;
use React\Socket\SocketServer;

$config = require __DIR__ . '/config.php';

const WS_GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

function wsAcceptKey(string $key): string
{
    return base64_encode(sha1($key . WS_GUID, true));
}

function wsEncodeTextFrame(string $payload): string
{
    return wsEncodeFrame(0x1, $payload);
}

function wsEncodeFrame(int $opcode, string $payload): string
{
    $length = strlen($payload);
    $finOpcode = 0x80 | $opcode;

    if ($length <= 125) {
        $header = chr($finOpcode) . chr($length);
    } elseif ($length <= 65535) {
        $header = chr($finOpcode) . chr(126) . pack('n', $length);
    } else {
        $header = chr($finOpcode) . chr(127) . pack('J', $length);
    }

    return $header . $payload;
}

function wsDecodeFrames(string &$buffer, ConnectionInterface $conn): array
{
    $messages = [];

    while (strlen($buffer) >= 2) {
        $byte0 = ord($buffer[0]);
        $byte1 = ord($buffer[1]);
        $opcode = $byte0 & 0x0F;
        $masked = ($byte1 & 0x80) !== 0;
        $length = $byte1 & 0x7F;
        $offset = 2;

        if ($length === 126) {
            if (strlen($buffer) < 4) return $messages;
            $length = unpack('n', substr($buffer, 2, 2))[1];
            $offset = 4;
        } elseif ($length === 127) {
            if (strlen($buffer) < 10) return $messages;
            $length = unpack('J', substr($buffer, 2, 8))[1];
            $offset = 10;
        }

        $maskLen = $masked ? 4 : 0;
        $frameTotal = $offset + $maskLen + $length;

        if (strlen($buffer) < $frameTotal) {
            return $messages;
        }

        $mask = $masked ? substr($buffer, $offset, 4) : '';
        $payload = substr($buffer, $offset + $maskLen, $length);

        if ($masked) {
            $unmasked = '';
            for ($i = 0; $i < $length; $i++) {
                $unmasked .= $payload[$i] ^ $mask[$i % 4];
            }
            $payload = $unmasked;
        }

        if ($opcode === 0x1) {
            $messages[] = $payload;
        } elseif ($opcode === 0x8) {
            $messages[] = null;
        } elseif ($opcode === 0x9) {
            // Ping - must reply with Pong carrying the same payload (RFC 6455 5.5.3),
            // otherwise heartbeat-enabled clients (ESP32 Master) conclude the
            // connection is dead and disconnect/reconnect in a tight loop.
            $conn->write(wsEncodeFrame(0xA, $payload));
        }

        $buffer = substr($buffer, $frameTotal);
    }

    return $messages;
}

class MasterBridge
{
    private ?ConnectionInterface $master = null;

    public function setMaster(?ConnectionInterface $conn): void
    {
        $this->master = $conn;
    }

    public function isConnected(): bool
    {
        return $this->master !== null;
    }

    public function sendToMaster(string $command): bool
    {
        if ($this->master === null) {
            return false;
        }

        $this->master->write(wsEncodeTextFrame($command));
        return true;
    }
}

$loop = Loop::get();
$bridge = new MasterBridge();

$wsServer = new SocketServer("0.0.0.0:{$config['ws_port']}", [], $loop);
$wsServer->on('connection', function (ConnectionInterface $conn) use ($bridge) {
    $handshakeDone = false;
    $buffer = '';

    $conn->on('data', function ($chunk) use (&$handshakeDone, &$buffer, $conn, $bridge) {
        $buffer .= $chunk;

        if (!$handshakeDone) {
            if (!str_contains($buffer, "\r\n\r\n")) {
                return;
            }

            if (!preg_match('/Sec-WebSocket-Key:\s*(.+)\r\n/i', $buffer, $m)) {
                $conn->end();
                return;
            }

            $accept = wsAcceptKey(trim($m[1]));
            $response = "HTTP/1.1 101 Switching Protocols\r\n" .
                "Upgrade: websocket\r\n" .
                "Connection: Upgrade\r\n" .
                "Sec-WebSocket-Accept: {$accept}\r\n\r\n";
            $conn->write($response);

            $handshakeDone = true;
            $buffer = '';
            $bridge->setMaster($conn);
            echo "[WS] ESP32 Master terhubung ({$conn->getRemoteAddress()})\n";
            return;
        }

        foreach (wsDecodeFrames($buffer, $conn) as $message) {
            if ($message === null) {
                $conn->end();
                return;
            }
            echo "[WS] Dari Master: {$message}\n";
        }
    });

    $conn->on('close', function () use (&$handshakeDone, $conn, $bridge) {
        if ($handshakeDone) {
            $bridge->setMaster(null);
            echo "[WS] ESP32 Master terputus\n";
        }
    });
});
echo "[WS] Menunggu koneksi ESP32 Master di port {$config['ws_port']}...\n";

$controlServer = new SocketServer("127.0.0.1:{$config['control_port']}", [], $loop);
$controlServer->on('connection', function (ConnectionInterface $conn) use ($bridge, $config) {
    $buffer = '';
    $conn->on('data', function ($chunk) use (&$buffer, $conn, $bridge, $config) {
        $buffer .= $chunk;
        if (!str_contains($buffer, "\n")) {
            return;
        }

        $line = trim($buffer);
        $payload = json_decode($line, true);

        if (!is_array($payload) || !hash_equals($config['secret'], (string) ($payload['secret'] ?? ''))) {
            $conn->write("ERROR:unauthorized\n");
            $conn->end();
            return;
        }

        $command = (string) ($payload['command'] ?? '');
        if ($command === '') {
            $conn->write("ERROR:empty_command\n");
            $conn->end();
            return;
        }

        if (!$bridge->isConnected()) {
            $conn->write("ERROR:no_master\n");
            $conn->end();
            return;
        }

        $bridge->sendToMaster($command);
        $conn->write("OK\n");
        $conn->end();
    });
});
echo "[CTRL] Menunggu perintah dari Laravel di 127.0.0.1:{$config['control_port']}...\n";

$loop->run();
