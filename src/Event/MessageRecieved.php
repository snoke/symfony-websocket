<?php

namespace Snoke\Websocket\Event;

use Symfony\Contracts\EventDispatcher\Event;
use React\Socket\ConnectionInterface;

class MessageRecieved extends Event
{
    public const NAME = 'websocket.message_received';

    private $connection;
    private $payload;

    public function __construct(ConnectionInterface $connection, $payload)
    {
        $this->connection = $connection;
        $this->payload = $payload;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }

    public function getPayload()
    {
        return $this->payload;
    }
}