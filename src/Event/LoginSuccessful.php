<?php

namespace Snoke\Websocket\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\Websocket\Security\ConnectionWrapper;

class LoginSuccessful extends AbstractEvent
{
    public const NAME = 'websocket.login_successful';


    public function __construct(ArrayCollection $channels, ArrayCollection $connections, ?ConnectionWrapper $connection)
    {
        parent::__construct($channels,$connections,$connection);
    }
}