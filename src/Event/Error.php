<?php

namespace Snoke\Websocket\Event;

use Snoke\Websocket\Security\ConnectionWrapper;

class Error  extends AbstractEvent
{
    public const NAME = 'websocket.on_error';
    public function __construct(array $connections, ConnectionWrapper $connection)
    {
        parent::__construct($connections,$connection);
    }
}