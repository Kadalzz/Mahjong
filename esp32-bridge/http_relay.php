<?php

$config = require __DIR__ . '/config.php';

$body = file_get_contents('php://input');

$socket = @stream_socket_client(
    "tcp://127.0.0.1:{$config['control_port']}",
    $errno,
    $errstr,
    5
);

if ($socket === false) {
    http_response_code(502);
    echo "ERROR:relay_unreachable:{$errstr}";
    exit;
}

fwrite($socket, rtrim($body, "\n") . "\n");
$response = trim((string) fgets($socket));
fclose($socket);

header('Content-Type: text/plain');
echo $response;
