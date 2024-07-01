<?php

namespace Snoke\Websocket\Service;

use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use Snoke\Websocket\Entity\Channel;
use Snoke\Websocket\Entity\Message;
use Snoke\Websocket\Entity\Request;
use Snoke\Websocket\Event\CommandReceived;
use Snoke\Websocket\Event\ServerStarted;
use Snoke\Websocket\Event\ConnectionClosed;
use Snoke\Websocket\Event\ConnectionEstablished;
use Snoke\Websocket\Event\MessageReceived;
use Snoke\Websocket\Event\Error;
use Snoke\Websocket\Security\ConnectionWrapper;
use Snoke\Websocket\Service\Decoder;
use Snoke\Websocket\Service\Encoder;
use React\Socket\ConnectionInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WebsocketServer
{
    private ArrayCollection $connections;
    private ArrayCollection $channels;
    private LoggerInterface $logger;
    private EventDispatcherInterface $eventDispatcher;
    private Encoder $encoder;
    private Decoder $decoder;
    private ?SymfonyStyle $outputDecorator;

    public function __construct(
        Encoder $encoder,
        Decoder $decoder,
        LoggerInterface $logger,
        EventDispatcherInterface $eventDispatcher,
        array $channels = ['main']
    ) {
        $this->connections = new ArrayCollection();
        $this->channels = new ArrayCollection();
        $this->logger = $logger;
        $this->eventDispatcher = $eventDispatcher;
        $this->encoder = $encoder;
        $this->decoder = $decoder;

        foreach($channels as $channel) {
            $this->channels->add(new Channel($channel));
        }
    }

    public function start(string $ip, string $port, bool $debug, SymfonyStyle $outputDecorator): void
    {
        $this->outputDecorator = $outputDecorator;
        $loop = Loop::get();

        // TODO: implement SSL config
        $context = [
            'tls' => [
                'local_cert' => '/path/to/your/server.pem',
                'local_pk' => '/path/to/your/private.key',
                'allow_self_signed' => true,
                'verify_peer' => false
            ]
        ];

        $socket = new SocketServer($ip . ':' . $port, [], $loop);
        $this->eventDispatcher->dispatch(new ServerStarted($this->channels, $this->connections, null), ServerStarted::NAME);

        $socket->on('connection', function (ConnectionInterface $connection) {
            $connectionWrapper = new ConnectionWrapper($this->encoder, $this->decoder, $connection);
            $this->connections->add($connectionWrapper);

            $connection->on('data', function ($data) use ($connectionWrapper) {
                try {
                    if (str_contains($data, 'Upgrade: websocket')) {
                        $this->performHandshake($connectionWrapper, $data);
                        $this->eventDispatcher->dispatch(new ConnectionEstablished($this->channels, $this->connections, $connectionWrapper), ConnectionEstablished::NAME);
                        $this->debugLog('New connection');
                    } else {
                        $request = $this->decoder->unmask($data);
                        if ($request && isset($request['type'])) {
                            switch ($request['type']) {
                                case 'message':
                                    $payload = $request['payload'];
                                    $channel = isset($request['channel']) ? $this->channels->filter(function (Channel $channel) use ($request) {
                                        return $request['channel'] === $channel->getIdentifier();
                                    })->first() : null;
                                    $message = new Message($connectionWrapper, $channel, $payload);
                                    $this->eventDispatcher->dispatch(new MessageReceived($this->channels, $this->connections, $connectionWrapper, $message), MessageReceived::NAME);
                                    $this->debugLog('Message Received');
                                    break;
                                case 'command':
                                    $this->eventDispatcher->dispatch(new CommandReceived($this->channels, $this->connections, $connectionWrapper, new Request($request['command'], $request['payload'])), CommandReceived::NAME);
                                    break;
                                case 'ping':
                                    $this->debugLog('Received ping');
                                    $connectionWrapper->write($this->encoder->mask('', 'pong'));
                                    break;
                                default:
                                    $this->eventDispatcher->dispatch(new Error($this->channels, $this->connections, $connectionWrapper, 'Unknown message type: ' . $request['type']), Error::NAME);
                                    $this->debugLog('Unknown message type: ' . $request['type']);
                                    break;
                            }
                        } else {
                            $this->debugLog('Invalid message format');
                        }
                    }
                } catch (\Throwable $e) {
                    $this->writeErrorLog($e);
                }
            });

            $connection->on('close', function () use ($connectionWrapper) {
                try {
                    $this->eventDispatcher->dispatch(new ConnectionClosed($this->channels, $this->connections, $connectionWrapper), ConnectionClosed::NAME);
                    $this->connections->removeElement($connectionWrapper);
                } catch (\Throwable $e) {
                    $this->writeErrorLog($e);
                }
            });

            $connection->on('error', function ($e) use ($connectionWrapper) {
                $this->debugLog('Error: ' . $e->getMessage());
                $this->writeErrorLog($e);
                try {
                    $this->eventDispatcher->dispatch(new Error($this->channels, $this->connections, $connectionWrapper, $e), Error::NAME);
                } catch (\Throwable $e) {
                    $this->debugLog('Error: ' . $e->getMessage());
                    $this->writeErrorLog($e);
                }
            });
        });

        $this->outputDecorator->success('Server started');
        $loop->run();
    }

    private function performHandshake(ConnectionWrapper $connectionWrapper, $data)
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
            $this->debugLog('Handshake failed: Sec-WebSocket-Key not found');
            $connectionWrapper->close();
        }
    }

    private function debugLog($message)
    {
        $this->logger->info('[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL);
    }

    private function writeErrorLog(\Throwable $e)
    {
        $this->logger->error('[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage(), $e);
    }
}
