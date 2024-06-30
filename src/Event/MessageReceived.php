<?php

namespace Snoke\Websocket\Event;

use Doctrine\Common\Collections\ArrayCollection;
use Snoke\Websocket\Entity\Message;
use Snoke\Websocket\Security\ConnectionWrapper;

class MessageReceived extends AbstractEvent
{
    public const NAME = 'websocket.message_received';
    private Message $message;


    public function __construct(ArrayCollection $channels, ArrayCollection $connections, ?ConnectionWrapper $connection, Message $message)
    {
        parent::__construct($channels,$connections,$connection);
        $this->message = $message;
    }
    public function getMessage(): Message
    {
        return $this->message;
    }
}