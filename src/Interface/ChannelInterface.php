<?php

namespace Snoke\Websocket\Interface;

use Doctrine\Common\Collections\Collection;
use Snoke\Websocket\Security\ConnectionWrapper;
use Symfony\Component\Security\Core\User\UserInterface;

interface ChannelInterface
{
    public function addConnection(ConnectionWrapper $user);
    public function getUsers(): Collection;
}