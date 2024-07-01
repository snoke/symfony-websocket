<?php

namespace Snoke\Websocket\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\Websocket\Security\ConnectionWrapper;

class ConnectionEstablished  extends AbstractEvent
{

    public function __construct(ArrayCollection $connections, ?ConnectionWrapper $connection)
    {
        parent::__construct($connections,$connection);
    }
}