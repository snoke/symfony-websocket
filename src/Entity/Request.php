<?php

namespace Snoke\Websocket\Entity;

class Request
{


    private array $body;
    private string $type;
    private string $command;

    public function __construct(string $command, array $body)
    {
        $this->body = $body;
        $this->command = $command;
        $this->type = "message";
    }
    public function getBody(): array {
        return $this->body;
    }
        public function getType(): string {
        return $this->type;
    }
        public function getCommand(): string {
        return $this->command;
    }


}