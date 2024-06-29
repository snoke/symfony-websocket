<?php
namespace Snoke\Websocket\Ratchet\WebSocket;
use Snoke\Websocket\Ratchet\ConnectionInterface;
use Snoke\Websocket\Ratchet\RFC6455\Messaging\MessageInterface;

interface MessageCallableInterface {
    public function onMessage(ConnectionInterface $conn, MessageInterface $msg);
}
