<?php

namespace Snoke\Websocket\Entity;

class Response
{


    private mixed $body;
    private string $command;

    public function __construct(string $command,mixed $body, private readonly string $type = 'text', private readonly bool $masked = true)
    {
        $this->body = $body;
        $this->command = $command;
    }
    public function getBody(): mixed {
        return $this->body;
    }
    public function getCommand(): string {
        return $this->command;
    }
    public function getType(): string {
        return $this->type;
    }
    public function isMasked(): bool {
        return $this->masked;
    }


}