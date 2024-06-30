<?php

namespace Snoke\Websocket\Event;

use Snoke\Websocket\Security\ConnectionWrapper;

class LoginSuccessful extends AbstractEvent
{
    public const NAME = 'websocket.login_successful';

    public function __construct(array $connections, ConnectionWrapper $connection)
    {
        parent::__construct($connections,$connection);
    }
}