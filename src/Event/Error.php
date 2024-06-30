<?php

namespace Snoke\Websocket\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\Websocket\Security\ConnectionWrapper;

class Error  extends AbstractEvent
{
    public const NAME = 'websocket.on_error';

    private  $error;


    public function __construct(ArrayCollection $channels, ArrayCollection $connections, ?ConnectionWrapper $connection,  $error)
    {
        parent::__construct($channels,$connections,$connection);
        $this->error = $error;
    }
    public function getError()
    {
        return $this->error;
    }
}