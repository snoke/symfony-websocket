<?php

namespace Snoke\Websocket\Event;

use Symfony\Contracts\EventDispatcher\Event;
use React\Socket\ConnectionInterface;

class Error extends Event
{
    public const NAME = 'websocket.on_error';

    private $error;

    public function __construct($error)
    {
        $this->error = $error;
    }

    public function getError()
    {
        return $this->error;
    }
}