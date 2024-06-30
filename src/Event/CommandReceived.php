<?php

namespace Snoke\Websocket\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\Websocket\Entity\Request;
use Snoke\Websocket\Security\ConnectionWrapper;

class CommandReceived extends AbstractEvent
{
    public const NAME = 'websocket.command_received';
    private Request $request;


    public function __construct(ArrayCollection $channels, ArrayCollection $connections, ?ConnectionWrapper $connection, Request $request)
    {
        parent::__construct($channels,$connections,$connection);
        $this->request = $request;
    }
    public function getRequest(): Request
    {
        return $this->request;
    }
}