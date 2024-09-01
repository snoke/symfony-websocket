<?php

namespace Snoke\Websocket\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\Websocket\ConnectionWrapper;

class AbstractFrame extends AbstractEvent
{
    public function __construct(protected  ArrayCollection $connections, protected  ?ConnectionWrapper $connection, protected  mixed $frame)
    {
        parent::__construct($connections,$connection);
    }
    public function getFrame(): mixed
    {
        return $this->frame;
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