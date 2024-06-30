<?php

namespace Snoke\Websocket\Event;

use Snoke\Websocket\Security\ConnectionWrapper;

class ConnectionEstablished  extends AbstractEvent
{
    public const NAME = 'websocket.connection_established';
    public function __construct(array $connections, ConnectionWrapper $connection)
    {
        parent::__construct($connections,$connection);
    }
}