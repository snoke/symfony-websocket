<?php

namespace Snoke\Websocket\Event;

use Snoke\Websocket\Security\ConnectionWrapper;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractEvent extends Event
{
    private ConnectionWrapper $connection;
    private array $connections;

    public function __construct(array $connections, ConnectionWrapper $connection)
    {
        $this->connections = $connections;
        $this->connection = $connection;
    }

    public function getConnection(): ConnectionWrapper
    {
        return $this->connection;
    }
    public function getConnections(): array
    {
        return $this->connections;
    }
}