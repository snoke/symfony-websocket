<?php

namespace Snoke\Websocket\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\Websocket\Security\ConnectionWrapper;
use Symfony\Contracts\EventDispatcher\Event;

abstract class AbstractEvent extends Event
{
    private ?ConnectionWrapper $connection;
    private ArrayCollection $connections;
    private ArrayCollection $channels;

    public function __construct(ArrayCollection $channels,ArrayCollection $connections, ?ConnectionWrapper $connection)
    {
        $this->connections = $connections;
        $this->connection = $connection;
        $this->channels = $channels;
    }

    public function getChannels(): ArrayCollection {
        return $this->channels;
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