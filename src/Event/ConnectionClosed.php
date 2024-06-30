<?php

namespace Snoke\Websocket\Event;

use Snoke\Websocket\Security\ConnectionWrapper;

class ConnectionClosed extends AbstractEvent
{
    public const NAME = 'websocket.connection_closed';

    public function __construct(array $connections, ConnectionWrapper $connection)
    {
        parent::__construct($connections,$connection);
    }
}