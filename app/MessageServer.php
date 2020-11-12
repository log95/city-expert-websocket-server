<?php

namespace App;

use Ratchet\ConnectionInterface;
use Ratchet\Wamp\WampServerInterface;

class MessageServer implements WampServerInterface {

    protected $subscribedTopics = array();

    /**
     * @param ConnectionInterface $conn
     * @param \Ratchet\Wamp\Topic|string $topic. Когда в js-е подписваемся на какой-то канал, здесь это называется
     *      топик. А строка указанная в js-е это id топика.
     *      Также данный объект хранит в себе всех (subscribers), кто подписался на него (топик).
     */
    public function onSubscribe(ConnectionInterface $conn, $topic) {
        $this->subscribedTopics[$topic->getId()] = $topic;
    }
    public function onUnSubscribe(ConnectionInterface $conn, $topic) {
    }
    public function onOpen(ConnectionInterface $conn) {
    }
    public function onClose(ConnectionInterface $conn) {
    }
    public function onCall(ConnectionInterface $conn, $id, $topic, array $params) {
        // In this application if clients send data it's because the user hacked around in console
        $conn->callError($id, $topic, 'You are not allowed to make calls')->close();
    }
    public function onPublish(ConnectionInterface $conn, $topic, $event, array $exclude, array $eligible) {
        // In this application if clients send data it's because the user hacked around in console
        $conn->close();
    }
    public function onError(ConnectionInterface $conn, \Exception $e) {
    }


    /**
     * Смысл, что функция находится в этом файле, чтобы у нас был доступ к $subscribedTopics.
     * @param $post
     */
    public function onBlogEntry($post) {
        print_r($post);
        ob_flush();
        flush();
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
