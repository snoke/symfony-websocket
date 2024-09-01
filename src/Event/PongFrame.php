<?php

namespace Snoke\Websocket\Event;

 use Doctrine\Common\Collections\ArrayCollection;
 use Snoke\Websocket\ConnectionWrapper;

 class PongFrame extends AbstractFrame
 {
     public function __construct(ArrayCollection $connections, ?ConnectionWrapper $connection, mixed $frame)
     {
         parent::__construct($connections, $connection, $frame);
     }
 }