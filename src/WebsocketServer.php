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
    private const GUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    private ArrayCollection $connections;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;
    private Encoder $encoder;
    private Decoder $decoder;
    private ?SymfonyStyle $outputDecorator;
    private ParameterBagInterface $params;
    private bool $debug;

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
        $this->debug = $debug;
        $this->outputDecorator = $outputDecorator;
        $loop = Loop::get();

        $context = $this->params->get('snoke_websocket.context');

        $socket = new SocketServer($ip . ':' . $port, $context, $loop);
        $this->eventDispatcher->dispatch(new ServerStarted($this->connections, null));

        $socket->on('connection', function (ConnectionInterface $connection) {
            $connectionWrapper = new ConnectionWrapper($this->encoder, $connection);
            $this->connections->add($connectionWrapper);

            $connection->on('data', function ($data) use ($connectionWrapper) {
                try {
                    if (str_contains($data, 'Upgrade: websocket')) {
                        $this->performHandshake($connectionWrapper, $data);
                        $this->eventDispatcher->dispatch(new ConnectionEstablished($this->connections, $connectionWrapper));
                        $this->debugLog('New connection from ' . $connectionWrapper->getRemoteAddress());
                    } else {
                        $request = $this->decoder->unmask($data);
                        if ($request) {
                            if (isset($request['type'] ) && $request['type'] === 'ping') {
                                $this->debugLog('Received ping');
                                $connectionWrapper->write($this->encoder->mask('', 'pong'));
                            } else {
                                $this->debugLog('Data received:' . is_string($request) ? $request : json_encode($request));
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
                $this->eventDispatcher->dispatch(new Error($this->connections, $connectionWrapper, $e));
                $this->writeErrorLog($e);
            });
        });

        $this->outputDecorator->success('Server started on port ' .$port );
        $loop->run();
    }

    private function performHandshake(ConnectionWrapper $connectionWrapper, $data): void
    {
        if (preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $data, $matches)) {
            $key = $matches[1];
            $acceptKey = base64_encode(pack('H*', sha1($key . self::GUID)));
            $headers = [
                "HTTP/1.1 101 Switching Protocols",
                "Upgrade: websocket",
                "Connection: Upgrade",
                "Sec-WebSocket-Accept: $acceptKey"
            ];
            $headers = implode("\r\n", $headers) . "\r\n\r\n";

            $connectionWrapper->write($headers);
        } else {
            $connectionWrapper->close();
            throw new Exception("Handshake failed: Sec-WebSocket-Key not found");
        }
    }

    private function debugLog($message): void
    {
        $date = date('Y-m-d H:i:s');
        if ($this->debug) {
            $this->outputDecorator->info('[' . $date . '] ' . $message);
        }
        $this->logger->info('[' . $date . '] ' . $message . PHP_EOL);
    }

    private function writeErrorLog(\Throwable $e): void
    {
        $date = date('Y-m-d H:i:s');
        if ($this->debug) {
            $this->outputDecorator->error('[' . $date . '] ' . $e->getMessage());
        }
        $this->logger->error('[' . $date . '] ' . $e->getMessage(), (array)$e);
    }
}
