<?php

namespace Snoke\Websocket\Event;

use Snoke\Websocket\Security\ConnectionWrapper;

class LoginFailed extends AbstractEvent
{
    public const NAME = 'websocket.login_failed';
    public function __construct(array $connections, ConnectionWrapper $connection)
    {
        parent::__construct($connections,$connection);
    }
}