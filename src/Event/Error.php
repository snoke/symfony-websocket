<?php

namespace Snoke\Websocket\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\Websocket\Security\ConnectionWrapper;
use Throwable;

class Error  extends AbstractEvent
{

    private Throwable $error;

    public function __construct(ArrayCollection $connections, ?ConnectionWrapper $connection,  Throwable $error)
    {
        parent::__construct($connections,$connection);
        $this->error = $error;
    }
    public function getError()
    {
        return $this->error;
    }
}