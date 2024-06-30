<?php

namespace Snoke\Websocket\Security;

use Doctrine\Common\Collections\ArrayCollection;
use React\Socket\ConnectionInterface;
use React\Stream\WritableStreamInterface;
use Snoke\Websocket\Entity\Channel;
use Snoke\Websocket\Entity\Response;
use Snoke\Websocket\Service\Decoder;
use Snoke\Websocket\Service\Encoder;
use Symfony\Component\Security\Core\User\UserInterface;

class ConnectionWrapper implements ConnectionInterface
{
    private int $id;
    private ?UserInterface $user;
    private ConnectionInterface $connection;
    private ArrayCollection $channels;

    public function __construct(
        private readonly Encoder $encoder,
        private readonly Decoder $decoder,
        ConnectionInterface $connection
    ) {
        $this->channels = new ArrayCollection();
        $this->id = spl_object_id($connection);
        $this->connection = $connection;
    }

    /**
     * @return ArrayCollection
     */
    public function getChannels(): ArrayCollection
    {
        return $this->channels;
    }
    public function addChannel(Channel $channel): void {
        if (!$this->channels->contains($channel)) {
            $this->channels->add($channel);
        }
    }
    public function removeChannel(Channel $channel): void {
        if ($this->channels->contains($channel)) {
            $this->channels->removeElement($channel);
        }
    }

    public function sendResponse(Response $response) {
        $this->write($this->encoder->mask(json_encode([
            'type' => $response->getCommand(),
            'payload' => $response->getBody(),
        ]),$response->getType(),$response->isMasked()));
    }

    public function getId(): int
    {
        return $this->id;
    }
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }
    public function setUser(UserInterface $user): ?static
    {
        $this->user = $user;
        return $this;
    }
    public function getRemoteAddress(): ?string
    {
        return $this->connection->getRemoteAddress();
    }
    public function getLocalAddress(): ?string
    {
        return $this->connection->getLocalAddress();
    }
    public function resume(): void
    {
        $this->connection->resume();
    }
    public function listeners($event = null): void
    {
        $this->connection->listeners($event);
    }
    public function removeListener($event, callable $listener): void
    {
        $this->connection->removeListener($event, $listener);
    }
    public function removeAllListeners($event = null): void
    {
        $this->connection->removeAllListeners($event);
    }
    public function isWritable(): void
    {
        $this->connection->isWritable();
    }
    public function isReadable(): void
    {
        $this->connection->isReadable();
    }
    public function pause(): void
    {
        $this->connection->pause();
    }
    public function once($event, callable $listener): void
    {
        $this->connection->once($event, $listener);
    }
    public function pipe(WritableStreamInterface $dest, array $options = array()): void
    {
        $this->connection->pipe($dest, $options);
    }
    public function end($data = null): void
    {
        $this->connection->end($data);
    }
    public function emit($event, array $arguments = []): void
    {
        $this->connection->emit($event, $arguments);
    }
    public function close(): void
    {
        $this->connection->close();
    }
    public function write($data): void
    {
        $this->connection->write($data);
    }
    public function on($event, callable $listener): void
    {
        $this->connection->on($event, $listener);
    }
}