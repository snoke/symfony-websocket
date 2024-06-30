<?php

namespace Snoke\Websocket\Event;

use Symfony\Contracts\EventDispatcher\Event;
use React\Socket\ConnectionInterface;

class MessageSent extends Event
{
    public const NAME = 'websocket.message_sent';

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