<?php
namespace Snoke\Websocket\Ratchet\WebSocket;
use Snoke\Websocket\Ratchet\RFC6455\Messaging\MessageBuffer;

class ConnContext {
    /**
     * @var \Ratchet\WebSocket\WsConnection
     */
    public $connection;

    /**
     * @var \Ratchet\RFC6455\Messaging\MessageBuffer;
     */
    public $buffer;

    public function __construct(WsConnection $conn, MessageBuffer $buffer) {
        $this->connection = $conn;
        $this->buffer = $buffer;
    }
}
