<?php

namespace Snoke\Websocket\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Snoke\Websocket\Security\ConnectionWrapper;

class Channel
{
    private Collection $connections;
    private string $identifier;
    private ArrayCollection $messages;

    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
        $this->connections = new ArrayCollection();
        $this->messages = new ArrayCollection();
    }
    public function getIdentifier(): string {
        return $this->identifier;
    }

    /**
     * @return Collection<int, ConnectionWrapper>
     */
    public function getConnections(): Collection
    {
        return $this->connections;
    }

    public function addConnection(ConnectionWrapper $connection): static
    {
        if (!$this->connections->contains($connection)) {
            $this->connections->add($connection);
        }

        return $this;
    }

    public function removeConnection(ConnectionWrapper $connection): static
    {
        $this->connections->removeElement($connection);

        return $this;
    }

    /**
     * @return Collection<int, Message>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(Message $message): static
    {
        if (!$this->messages->contains($message)) {
            $this->messages->add($message);
        }

        return $this;
    }

    public function removeMessage(Message $message): static
    {
        $this->messages->removeElement($message);

        return $this;
    }
}