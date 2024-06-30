<?php

namespace Snoke\Websocket\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerInterface;
use React\EventLoop\Loop;
use React\Socket\SocketServer;
use Snoke\Websocket\Entity\Channel;
use Snoke\Websocket\Entity\Message;
use Snoke\Websocket\Entity\Request;
use Snoke\Websocket\Event\CommandReceived;
use Snoke\Websocket\Event\LoginFailed;
use Snoke\Websocket\Event\LoginSuccessful;
use Snoke\Websocket\Event\ServerStarted;
use Snoke\Websocket\Security\Authenticator;
use Snoke\Websocket\Event\ConnectionClosed;
use Snoke\Websocket\Event\ConnectionEstablished;
use Snoke\Websocket\Event\MessageReceived;
use Snoke\Websocket\Event\Error;
use Snoke\Websocket\Security\ConnectionWrapper;
use Snoke\Websocket\Service\Decoder;
use Snoke\Websocket\Service\Encoder;
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
    private ArrayCollection $connections;
    private ArrayCollection $channels;
    private LoggerInterface $logger;

    public function __construct(private readonly Encoder $encoder, private readonly Decoder $decoder, LoggerInterface $logger,EventDispatcherInterface $eventDispatcher, Authenticator $authenticator)
    {
        $this->connections = new ArrayCollection();
        $this->channels = new ArrayCollection();
        $this->channels->add(new Channel('main'));
        $this->authenticator = $authenticator;
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;

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
        $socket = new SocketServer($this->input->getOption('ip').':' . $this->input->getOption('port'), [], $loop);
        $this->eventDispatcher->dispatch(new ServerStarted($this->channels, $this->connections, null),ServerStarted::NAME);

        $socket->on('connection', function (ConnectionInterface $connection) {
            $connectionWrapper = new ConnectionWrapper($this->encoder, $this->decoder,$connection);
            $connectionWrapper->addChannel($this->channels->first());
            $this->connections->add($connectionWrapper);

                $connection->on('data', function ($data) use ($connectionWrapper) {

                    try {

                        if (str_contains($data, 'Upgrade: websocket')) {
                            $this->performHandshake($connectionWrapper, $data);
                            $this->eventDispatcher->dispatch(new ConnectionEstablished($this->channels,$this->connections, $connectionWrapper),ConnectionEstablished::NAME);
                            $this->debugLog('New connection');
                        } else {
                            $request = $this->unmask($data);
                            if ($request && isset($request['type'])) {
                                switch ($request['type']) {
                                    case 'message':
                                        $payload = $request['payload'];
                                        $channel =isset($request['channel'])  ? $this->channels->filter(function (Channel $channel) use ($request) {
                                            return $request['channel'] === $channel->getIdentifier();
                                        })->first() : null;
                                        $message = new Message($connectionWrapper,$channel,$payload);
                                        $this->eventDispatcher->dispatch(new MessageReceived($this->channels,$this->connections, $connectionWrapper, $message),MessageReceived::NAME);
                                        $this->debugLog('Message Received');
                                        //$this->respondMessage($connection, $message['payload']);
                                        //$this->broadcastMessage($connection, $message['payload']);
                                        break;
                                    case 'command':
                                        $this->eventDispatcher->dispatch(new CommandReceived($this->channels,$this->connections, $connectionWrapper, new Request($request['command'],$request['payload'])),CommandReceived::NAME);

                                        break;
                                    case 'server':
                                        $this->debugLog('server command');
                                        break;
                                    case 'ping':
                                        $this->debugLog('Received ping');
                                        $connectionWrapper->write($this->mask('', 'pong'));
                                        break;
                                    default:
                                        $this->eventDispatcher->dispatch(new Error($this->channels,$this->connections, $connectionWrapper, 'Unknown message type: ' . $request['type'],$message),Error::NAME);
                                        $this->debugLog('Unknown message type: ' . $request['type']);
                                        break;
                                }
                            } else {
                                $this->debugLog('Invalid message format');
                            }
                        }
                    } catch(\Exception $e) {
                        $this->writeErrorLog($e);
                    }

                });

                $connection->on('close', function () use ($connectionWrapper){

                    try {
                        $this->eventDispatcher->dispatch(new ConnectionClosed($this->channels,$this->connections, $connectionWrapper),ConnectionClosed::NAME);
                        $this->connections->removeElement($connectionWrapper);
                    } catch(\Exception $e) {
                        $this->writeErrorLog($e);
                    }

                });

                $connection->on('error', function ($e) use ($connectionWrapper){

                    try {
                        $this->eventDispatcher->dispatch(new Error($this->channels,$this->connections, $connectionWrapper,$e),Error::NAME);
                        $this->debugLog('Error: ' . $e->getMessage());
                    } catch(\Exception $e) {
                        $this->writeErrorLog($e);
                    }
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
            $this->debugLog('Handshake failed: Sec-WebSocket-Key not found');
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
            ]),'text',true));
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

    private function debugLog($message)
    {
        $this->logger->info('[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL);
        if (filter_var($this->input->getOption('debug'), FILTER_VALIDATE_BOOLEAN)) {
            $this->outputDecorator->note('[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL);
        }
    }

    private function writeErrorLog(\Exception $e)
    {
        $this->outputDecorator->error('[' . date('Y-m-d H:i:s') . '] ' . $e . PHP_EOL);
        $this->logger->error('[' . date('Y-m-d H:i:s') . '] ' . $e->getMessage(),$e);
    }
}
