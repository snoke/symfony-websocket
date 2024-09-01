<?php

namespace Snoke\Websocket\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\Websocket\ConnectionWrapper;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractEvent extends Event
{

    public function __construct(protected  ArrayCollection $connections, protected  ?ConnectionWrapper $connection)
    {
    }

    public function getConnection(): ?ConnectionWrapper
    {
        return $this->connection;
    }

    public function getConnections(): ArrayCollection
    {
        return $this->connections;
    }
}