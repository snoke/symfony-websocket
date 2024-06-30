<?php

namespace Snoke\Websocket\Event;

use Symfony\Contracts\EventDispatcher\Event;
use React\Socket\ConnectionInterface;

class ConnectionEstablished extends Event
{
    public const NAME = 'websocket.connection_established';

    private $connection;
    private $payload;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function getConnection(): ConnectionInterface
    {
        return $this->connection;
    }
}