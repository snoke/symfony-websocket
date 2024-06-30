<?php

namespace Snoke\Websocket\Entity;

use Snoke\Websocket\Security\ConnectionWrapper;

class Message
{

    private ConnectionWrapper $connection;
    private ?Channel $channel;
    private mixed $body;

    public function __construct(ConnectionWrapper $connection, ?Channel $channel, mixed $body)
    {
        $this->connection = $connection;
        $this->channel = $channel;
        $this->body = $body;
    }

    public function getChannel(): ?Channel {
        return $this->channel;
    }

    public function getBody(): mixed {
        return $this->body;
    }

    /**
     * @return ConnectionWrapper
     */
    public function getConnection(): ConnectionWrapper
    {
        return $this->connection;
    }

}