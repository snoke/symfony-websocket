<?php

namespace Snoke\Websocket\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\Websocket\Entity\Message;
use Snoke\Websocket\Entity\Request;
use Snoke\Websocket\Security\ConnectionWrapper;

class HandshakeFailed
{
    public function __construct(protected  ArrayCollection $connections, protected   ?ConnectionWrapper $connection, protected  mixed $frame)
    {
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