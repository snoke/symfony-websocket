<?php

namespace Snoke\Websocket\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\Websocket\Entity\Message;
use Snoke\Websocket\Entity\Request;
use Snoke\Websocket\Security\ConnectionWrapper;

class RequestReceived extends AbstractEvent
{
    private array $request;

    public function __construct(ArrayCollection $connections, ?ConnectionWrapper $connection, array $request)
    {
        parent::__construct($connections,$connection);
        $this->request = $request;
    }
    public function getRequest(): array
    {
        return $this->request;
    }
}