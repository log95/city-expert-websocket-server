<?php

use App\MessageServer;
use Dotenv\Dotenv;

require dirname(__DIR__) . '/vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

$loop   = React\EventLoop\Factory::create();
$pusher = new MessageServer();

// Listen for the web server to make a ZeroMQ push after an ajax request
$context = new React\ZMQ\Context($loop);
//$pull = $context->getSocket(ZMQ::SOCKET_PULL);
$pull = $context->getSocket(ZMQ::SOCKET_REP);
// TOOD: учитывать, что много инстансов symfony
// TODO: 0.0.0.0 должно быть?
$pull->bind('tcp://0.0.0.0:5555'); // Binding to 127.0.0.1 means the only client that can connect is itself
//$pull->bind('tcp://127.0.0.1:5555'); // Binding to 127.0.0.1 means the only client that can connect is itself
// Точка входа для всех пришедших событий с бекенда.
$pull->on('message', function ($message) use ($pusher, $pull) {
    $pusher->onBlogEntry($message, $pull);
});

// Set up our WebSocket server for clients wanting real-time updates
$webSock = new React\Socket\Server('0.0.0.0:8087', $loop); // Binding to 0.0.0.0 means remotes can connect
$webServer = new Ratchet\Server\IoServer(
    new Ratchet\Http\HttpServer(
        new Ratchet\WebSocket\WsServer(
            $pusher
        )
    ),
    $webSock
);

$loop->run();
