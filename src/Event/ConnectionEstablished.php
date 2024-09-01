<?php

namespace Snoke\Websocket\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\Websocket\ConnectionWrapper;

class ConnectionEstablished  extends AbstractEvent
{


    public function __construct(protected  ArrayCollection $connections, protected  ?ConnectionWrapper $connection)
    {
        parent::__construct($connections,$connection);
    }
}