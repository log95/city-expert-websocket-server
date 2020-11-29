<?php

namespace App;

use Firebase\JWT\JWT;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;
use React\ZMQ\SocketWrapper;

class MessageServer implements MessageComponentInterface
{
    private array $userConnections = [];

    public function onOpen(ConnectionInterface $conn)
    {
        $queryString = $conn->httpRequest->getUri()->getQuery();

        parse_str($queryString, $queryParams);

        if (!$queryParams['token']) {
            throw new \RuntimeException('No auth token provided.');
        }

        $payload = (array) JWT::decode($queryParams['token'], $_SERVER['JWT_PUBLIC_KEY'], ['RS256']);

        if (!$payload['id']) {
            throw new \RuntimeException('User id is not defined.');
        }

        $userId = $payload['id'];

        if (!isset($this->userConnections[$userId])) {
            $this->userConnections[$userId] = [];
        }

        $this->userConnections[$userId][] = $conn;

        $conn->userId = $userId;

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

    public function onClose(ConnectionInterface $conn)
    {
        if ($conn->userId) {
            $keyConn = array_search($conn, $this->userConnections[$conn->userId], true);

            if ($keyConn !== false) {
                unset($this->userConnections[$conn->userId][$keyConn]);
            }
        }

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Error: {$e->getMessage()}\n";

        $conn->close();
    }


    public function handleMessageFromSocket(string $message, SocketWrapper $socket): void
    {
        $messageDecoded = json_decode($message, true);

        if (!$messageDecoded['USER_ID']) {
            $socket->send(json_encode([
                'TYPE' => 'ERROR',
                'MESSAGE' => 'User is not set.',
            ]));

            return;
        }

        if (!$this->isUserOnline($messageDecoded['USER_ID'])) {
            $socket->send(json_encode([
                'TYPE' => 'USER_IS_OFFLINE',
                'MESSAGE' => 'Couldn\'t send message because user is offline.',
            ]));

            return;
        }

        $this->sendMessageToUser($messageDecoded['USER_ID'], $message);

        $socket->send(json_encode([
            'TYPE' => 'SUCCESS',
            'MESSAGE' => 'Message is sended.',
        ]));
    }

    private function sendMessageToUser(int $userId, string $message): void
    {
        /** @var ConnectionInterface $conn */
        foreach ($this->userConnections[$userId] as $conn) {
            $conn->send($message);
        }
    }

    private function isUserOnline(int $userId): bool
    {
        return array_key_exists($userId, $this->userConnections);
    }
}
