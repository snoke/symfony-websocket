<?php

namespace Snoke\Websocket\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Exception;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use Snoke\Websocket\Event\RequestReceived;
use Snoke\Websocket\Event\ServerStarted;
use Snoke\Websocket\Event\ConnectionClosed;
use Snoke\Websocket\Event\ConnectionEstablished;
use Snoke\Websocket\Event\Error;
use Snoke\Websocket\Security\ConnectionWrapper;
use Snoke\Websocket\Service\Decoder;
use Snoke\Websocket\Service\Encoder;
use React\Socket\ConnectionInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WebsocketServer
{
    private ArrayCollection $connections;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;
    private Encoder $encoder;
    private Decoder $decoder;
    private ?SymfonyStyle $outputDecorator;
    private ParameterBagInterface $params;

    public function __construct(
        ParameterBagInterface $params,
        Encoder $encoder,
        Decoder $decoder,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->params = $params;
        $this->connections = new ArrayCollection();
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->encoder = $encoder;
        $this->decoder = $decoder;

    }

    public function start(SymfonyStyle $outputDecorator, string $ip, string $port, bool $debug): void
    {
        $this->outputDecorator = $outputDecorator;
        $loop = Loop::get();

        $context = $this->params->get('snoke_websocket.context');

        $socket = new SocketServer($ip . ':' . $port, $context, $loop);
        $this->eventDispatcher->dispatch(new ServerStarted($this->connections, null));

        $socket->on('connection', function (ConnectionInterface $connection) {
            $connectionWrapper = new ConnectionWrapper($this->encoder, $this->decoder, $connection);
            $this->connections->add($connectionWrapper);

            $connection->on('data', function ($data) use ($connectionWrapper) {
                try {
                    if (str_contains($data, 'Upgrade: websocket')) {
                        $this->performHandshake($connectionWrapper, $data);
                        $this->eventDispatcher->dispatch(new ConnectionEstablished($this->connections, $connectionWrapper));
                        $this->debugLog('New connection');
                    } else {
                        $request = $this->decoder->unmask($data);
                        if ($request && isset($request['type'])) {
                            if ($request['type'] === 'ping') {
                                $this->debugLog('Received ping');
                                $connectionWrapper->write($this->encoder->mask('', 'pong'));
                            } else {
                                $this->eventDispatcher->dispatch(new RequestReceived($this->connections, $connectionWrapper, $request));
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    $this->eventDispatcher->dispatch(new Error($this->connections, $connectionWrapper, $e));
                    $this->writeErrorLog($e);
                }
            });

            $connection->on('close', function () use ($connectionWrapper) {
                try {
                    $this->eventDispatcher->dispatch(new ConnectionClosed($this->connections, $connectionWrapper));
                    $this->connections->removeElement($connectionWrapper);
                } catch (\Throwable $e) {
                    $this->writeErrorLog($e);
                }
            });

            $connection->on('error', function ($e) use ($connectionWrapper) {
                $this->debugLog('Error: ' . $e->getMessage());
                $this->writeErrorLog($e);
                try {
                    $this->eventDispatcher->dispatch(new Error($this->connections, $connectionWrapper, $e));
                } catch (\Throwable $e) {
                    $this->debugLog('Error: ' . $e->getMessage());
                    $this->writeErrorLog($e);
                }
            });
        });

        $this->outputDecorator->success('Server started');
        $loop->run();
    }

    private function performHandshake(ConnectionWrapper $connectionWrapper, $data): void
    {
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $data, $matches)) {
            $key = $matches[1];
            $acceptKey = base64_encode(pack('H*', sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
            $headers = "HTTP/1.1 101 Switching Protocols\r\n";
            $headers .= "Upgrade: websocket\r\n";
            $headers .= "Connection: Upgrade\r\n";
            $headers .= "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";

            $connectionWrapper->write($headers);
        } else {
            $connectionWrapper->close();
            throw new Exception("Handshake failed: Sec-WebSocket-Key not found");
        }
    }

    private function debugLog($message): void
    {
        $this->logger->info('[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL);
    }

    private function writeErrorLog(\Throwable $e): void
    {
        $this->logger->error('[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage(), (array)$e);
    }
}
