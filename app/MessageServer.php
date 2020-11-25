<?php

namespace App;

use Firebase\JWT\JWT;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use React\ZMQ\SocketWrapper;

class MessageServer implements MessageComponentInterface
{
    private array $userToConnectionMap = [];

    public function onOpen(ConnectionInterface $conn)
    {
        $queryString = $conn->httpRequest->getUri()->getQuery();

        parse_str($queryString, $queryParams);

        if (!$queryParams['token']) {
            throw new \RuntimeException('No auth token provided.');
        }

        $payload = (array) JWT::decode($queryParams['token'], $_SERVER['JWT_PUBLIC_KEY'], ['RS256']);

        var_dump($payload);

        if ($payload['id']) {
            throw new \RuntimeException('User id is not defined.');
        }

        $userId = $payload['id'];

        // TODO: массив коннекшенов.
        $this->userToConnectionMap[$userId] = $conn;

        echo "New connection! ({$conn->resourceId})\n";
    }

    // TODO: удалить интерфейс?
    public function onMessage(ConnectionInterface $from, $msg) {
        $numRecv = count($this->users) - 1;
        echo sprintf('Connection %d sending message "%s" to %d other connection%s' . "\n"
            , $from->resourceId, $msg, $numRecv, $numRecv == 1 ? '' : 's');

        foreach ($this->users as $client) {
            if ($from !== $client) {
                // The sender is not the receiver, send to each client connected
                $client->send($msg);
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it, as we can no longer send it messages
        //$this->users->detach($conn);

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }


    /**
     * Смысл, что функция находится в этом файле, чтобы у нас был доступ к $subscribedTopics.
     * @param $post
     */
    public function onBlogEntry($post, SocketWrapper $socket) {
        $socket->send('TRUE 22');

        /*print_r($post);
        ob_flush();
        flush();*/
        return;

        $postData = json_decode($post, true);

        // If the lookup topic object isn't set there is no one to publish to
        if (!array_key_exists($postData['category'], $this->subscribedTopics)) {
            return;
        }

        $topic = $this->subscribedTopics[$postData['category']];

        // re-send the data to all the clients subscribed to that category
        $topic->broadcast($postData);
    }
}
