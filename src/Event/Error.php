<?php

namespace Snoke\Websocket\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\Websocket\Security\ConnectionWrapper;
use Throwable;

class Error  extends AbstractEvent
{

    public function __construct(protected  ArrayCollection $connections, protected  ?ConnectionWrapper $connection,  protected  Throwable $error)
    {
        parent::__construct($connections,$connection);
    }
    public function getError()
    {
        return $this->error;
    }
}