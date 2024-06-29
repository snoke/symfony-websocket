<?php
/*
 * Author: Stefan Sander <mail@stefan-sander.online>
 */

namespace Snoke\Websocket;

use Snoke\Websocket\Ratchet\ConnectionInterface;
use Snoke\Websocket\Ratchet\MessageComponentInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Base Websocket Server Module
 * collects client connections and listens for requests
 */
class Server implements MessageComponentInterface
{
    protected \SplObjectStorage $clients;
    protected SymfonyStyle $io;

    public function __construct()
    {

        $this->clients = new \SplObjectStorage();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function setInterface(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    /**
     * @param ConnectionInterface $conn
     * @return void
     */
    public function onOpen(ConnectionInterface $conn): void
    {
        $this->clients->attach($conn);
        $this->io->block("New connection from $conn->remoteAddress ($conn->resourceId)", 'INFO', 'fg=yellow', ' ', true);
    }

    /**
     * @param ConnectionInterface $from
     * @param $msg
     * @return void
     */
    public function onMessage(ConnectionInterface $from, $msg): void
    {
        $this->io->block($msg, 'USER REQUEST', 'fg=blue', ' ', true);
    }

    /**
     * @param ConnectionInterface $conn
     * @return void
     */
    public function onClose(ConnectionInterface $conn): void
    {
        $this->clients->detach($conn);
        $this->io->block("Connection dropped $conn->remoteAddress ($conn->resourceId)", 'INFO', 'fg=yellow', ' ', true);
    }

    /**
     * @param ConnectionInterface $conn
     * @param \Exception $e
     * @return void
     */
    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        throw $e;
    }
}