<?php

namespace Snoke\Websocket\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\Websocket\Security\ConnectionWrapper;

class ConnectionClosed extends AbstractEvent
{
    public const NAME = 'websocket.connection_closed';


    public function __construct(ArrayCollection $channels, ArrayCollection $connections, ?ConnectionWrapper $connection)
    {
        parent::__construct($channels,$connections,$connection);
    }
}