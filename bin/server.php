<?php

use App\MessageServer;
use Dotenv\Dotenv;
use React\EventLoop\Factory;
use React\Socket\Server as SocketServer;
use React\ZMQ\Context as ZMQContext;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$loop = Factory::create();
$messageServer = new MessageServer();

// Listen messages from backend on this socket.
$context = new ZMQContext($loop);
$socket = $context->getSocket(ZMQ::SOCKET_REP);
// Binding to 0.0.0.0 means remotes can connect.
$socket->bind(sprintf('tcp://0.0.0.0:%s', $_SERVER['SOCKET_FOR_BACKEND_PORT']));
$socket->on('message', function ($message) use ($messageServer, $socket) {
    $messageServer->handleMessageFromSocket($message, $socket);
});

// WebSocket server for end users.
$webSock = new SocketServer(sprintf('0.0.0.0:%s', $_SERVER['WS_SERVER_PORT']), $loop);

$webServer = new IoServer(
    new HttpServer(
        new WsServer(
            $messageServer
        )
    ),
    $webSock
);
$loop->run();
