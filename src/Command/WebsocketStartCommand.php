<?php

namespace Snoke\Websocket\Command;

use React\EventLoop\Loop;
use React\Socket\SocketServer;
use Snoke\Websocket\Event\LoginFailed;
use Snoke\Websocket\Event\LoginSuccessful;
use Snoke\Websocket\Security\Authenticator;
use Snoke\Websocket\Event\ConnectionClosed;
use Snoke\Websocket\Event\ConnectionEstablished;
use Snoke\Websocket\Event\MessageReceived;
use Snoke\Websocket\Event\Error;
use Snoke\Websocket\Security\ConnectionWrapper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use React\Socket\ConnectionInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'websocket:start',
    description: 'Starts a websocket server',
)]
class WebsocketStartCommand extends Command
{
    protected SymfonyStyle $outputDecorator;
    protected EventDispatcherInterface  $eventDispatcher;
    private Authenticator $authenticator;
    private InputInterface $input;
    private array $connections;

    public function __construct(EventDispatcherInterface $eventDispatcher, Authenticator $authenticator)
    {
        $this->authenticator = $authenticator;
        $this->eventDispatcher = $eventDispatcher;
        $this->connections = [];

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('debug', null, InputOption::VALUE_OPTIONAL, 'debug','true')
            ->addOption('ip', null, InputOption::VALUE_OPTIONAL, 'custom ip','0.0.0.0')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'custom port','8080');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->outputDecorator = new SymfonyStyle($input, $output);
        $loop = Loop::get();
        // TODO: implement SSL config
        $context = [
            'tls' => [
                'local_cert'  => '/path/to/your/server.pem',
                'local_pk'    => '/path/to/your/private.key',
                'allow_self_signed' => true,
                'verify_peer' => false
            ]
        ];
        $socket = new SocketServer($input->getOption('ip').':' . $input->getOption('port'), [], $loop);

        $socket->on('connection', function (ConnectionInterface $connection) {

            $this->connections[spl_object_id($connection)] = $connectionWrapper = new ConnectionWrapper($connection);

            $connection->on('data', function ($data) use ($connectionWrapper) {
                if (str_contains($data, 'Upgrade: websocket')) {
                    $this->performHandshake($connectionWrapper, $data);
                    $this->eventDispatcher->dispatch(new ConnectionEstablished($this->connections, $connectionWrapper),ConnectionEstablished::NAME);
                    $this->log('New connection');
                } else {
                    $message = $this->unmask($data);
                    if ($message && isset($message['type'])) {
                        switch ($message['type']) {
                            case 'message':
                                $this->eventDispatcher->dispatch(new MessageReceived($this->connections, $connectionWrapper, $message),MessageReceived::NAME);
                                $this->log('Message Received');
                                //$this->respondMessage($connection, $message['payload']);
                                //$this->broadcastMessage($connection, $message['payload']);
                                break;
                            case 'login':
                                $user = $this->authenticator->authenticate($connectionWrapper, $message['payload']['identifier'],$message['payload']['password']);
                                $connectionWrapper->setUser($user);
                                if ($user) {
                                    $this->eventDispatcher->dispatch(new LoginSuccessful($this->connections, $connectionWrapper),LoginSuccessful::NAME);

                                    $this->log('Login successful');
                                } else {
                                    $this->log('Login failed');
                                    $this->eventDispatcher->dispatch(new LoginFailed($this->connections, $connectionWrapper),LoginFailed::NAME);
                                }
                                break;
                            case 'server':
                                $this->log('server command');
                                break;
                            case 'ping':
                                $this->log('Received ping');
                                $connectionWrapper->write($this->mask('', 'pong'));
                                break;
                            default:
                                $this->eventDispatcher->dispatch(new Error($this->connections, $connectionWrapper, 'Unknown message type: ' . $message['type'],$message),Error::NAME);
                                $this->log('Unknown message type: ' . $message['type']);
                                break;
                        }
                    } else {
                        $this->log('Invalid message format');
                    }
                }
            });

            $connection->on('close', function () use ($connectionWrapper){
                $this->eventDispatcher->dispatch(new ConnectionClosed($this->connections, $connectionWrapper),ConnectionClosed::NAME);
                $this->connections[$connectionWrapper->getId()] = null;
            });

            $connection->on('error', function ($e) use ($connectionWrapper){
                $this->eventDispatcher->dispatch(new Error($this->connections, $connectionWrapper, $e->getMessage(),$e),Error::NAME);
                $this->log('Error: ' . $e->getMessage());
            });
        });

        $this->outputDecorator->success('Server running');
        $loop->run();

        return Command::SUCCESS;
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
            $this->log('Handshake failed: Sec-WebSocket-Key not found');
            $connectionWrapper->close();
        }
    }

    private function unmask($data)
    {
        $bytes = unpack('C*', $data);
        $length = $bytes[2] & 127;

        if ($length == 126) {
            $masks = array_slice($bytes, 4, 4);
            $data = array_slice($bytes, 8);
        } elseif ($length == 127) {
            $masks = array_slice($bytes, 10, 4);
            $data = array_slice($bytes, 14);
        } else {
            $masks = array_slice($bytes, 2, 4);
            $data = array_slice($bytes, 6);
        }

        for ($i = 0; $i < count($data); ++$i) {
            $data[$i] = $data[$i] ^ $masks[$i % 4];
        }

        return json_decode(implode(array_map("chr", $data)), true);
    }

    private function broadcastMessage(ConnectionInterface $from, $message)
    {
        foreach ($this->connections as $connection) {
            if ($connection !== $from) {
                $connection->write($this->mask(json_encode([
                    'type' => 'message',
                    'payload' => $message,
                ])));
            }
        }
    }

    private function respondMessage(ConnectionInterface $from, $message)
    {
        $from->write($this->mask(json_encode([
                'type' => 'message',
                'payload' => $message,
            ])));
    }

    private function mask($payload, $type = 'text', $masked = false)
    {
        $frameHead = array();
        $payloadLength = strlen($payload);

        switch ($type) {
            case 'text':
                $frameHead[0] = 129;
                break;

            case 'close':
                $frameHead[0] = 136;
                break;

            case 'ping':
                $frameHead[0] = 137;
                break;

            case 'pong':
                $frameHead[0] = 138;
                break;
        }

        if ($payloadLength > 65535) {
            $payloadLengthBin = str_split(sprintf('%064b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 255 : 127;
            for ($i = 0; $i < 8; $i++) {
                $frameHead[$i + 2] = bindec($payloadLengthBin[$i]);
            }
            if ($frameHead[2] > 127) {
                return;
            }
        } elseif ($payloadLength > 125) {
            $payloadLengthBin = str_split(sprintf('%016b', $payloadLength), 8);
            $frameHead[1] = ($masked === true) ? 254 : 126;
            $frameHead[2] = bindec($payloadLengthBin[0]);
            $frameHead[3] = bindec($payloadLengthBin[1]);
        } else {
            $frameHead[1] = ($masked === true) ? $payloadLength + 128 : $payloadLength;
        }

        foreach (array_keys($frameHead) as $i) {
            $frameHead[$i] = chr($frameHead[$i]);
        }

        $frame = implode('', $frameHead);

        $mask = array();
        if ($masked === true) {
            for ($i = 0; $i < 4; $i++) {
                $mask[$i] = chr(rand(0, 255));
            }
            $frame .= implode('', $mask);
        }

        for ($i = 0; $i < $payloadLength; $i++) {
            $frame .= ($masked === true) ? $payload[$i] ^ $mask[$i % 4] : $payload[$i];
        }

        return $frame;
    }

    private function log($message)
    {
        if (filter_var($this->input->getOption('debug'), FILTER_VALIDATE_BOOLEAN)) {
            $this->outputDecorator->note('[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL);
        }
    }
}
