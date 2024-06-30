<?php

namespace Snoke\Websocket\Event;

use Symfony\Contracts\EventDispatcher\Event;
use React\Socket\ConnectionInterface;

class ServerStarted extends Event
{
    public const NAME = 'websocket.server_started';

    private string $ip;
    private string $port;

    public function __construct(string $ip,string $port)
    {
        $this->ip = $ip;
        $this->port = $port;
    }
    public function getIp(): string {
        return $this->ip;
    }
    public function getPort(): string {
        return $this->port;
    }
}