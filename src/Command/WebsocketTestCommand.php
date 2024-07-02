<?php
namespace Snoke\Websocket\Command;

use Snoke\Websocket\Service\Decoder;
use Snoke\Websocket\Service\Encoder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use React\EventLoop\Factory;
use React\Socket\Connector;
use React\EventLoop\Loop;
use Psr\Http\Message\ResponseInterface;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;

#[AsCommand(
    name: 'websocket:test',
    description: 'sends a test message to websocket server and prints responses',
)]
class WebsocketTestCommand extends Command
{
    private SymfonyStyle $outputDecorator;
    public function __construct(
        private readonly Encoder $encoder,
        private readonly Decoder $decoder
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('ip', null, InputOption::VALUE_OPTIONAL, 'custom ip', '127.0.0.1')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'custom port', '8080')
            ->addOption('message', null, InputOption::VALUE_OPTIONAL, 'Message to test','Hello World!')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Data type','text');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->outputDecorator = new SymfonyStyle($input, $output);
        $this->connect($input->getOption('ip'), (int)$input->getOption('port'),$input->getOption('message'),$input->getOption('type'));
        return Command::SUCCESS;
    }

    private function performHandshake(ConnectionInterface $connection) {
        // Perform WebSocket handshake
        $headers = "GET / HTTP/1.1\r\n";
        $headers .= "Host: localhost\r\n";
        $headers .= "Upgrade: websocket\r\n";
        $headers .= "Connection: Upgrade\r\n";
        $headers .= "Sec-WebSocket-Key: " . base64_encode(random_bytes(16)) . "\r\n";
        $headers .= "Sec-WebSocket-Version: 13\r\n\r\n";
        $connection->write($headers);
    }
    public function connect(string $host = '127.0.0.1', int $port = 8080, string $message = 'Hello World!', string $type = 'text'): void {

            $loop = Loop::get();
            $connector = new Connector($loop);
            $this->outputDecorator->note("connecting to " . $host . ":" . $port);
            $connector->connect($host.':' . $port)->then(function (ConnectionInterface $connection) use ($message, $type)
            {
                $this->outputDecorator->success("connection established");
                $this->outputDecorator->note("performing handshake");
                $this->performHandshake($connection);
                $connection->on('data', function ($data) use ($connection, $message, $type) {
                    if($pos = strpos($data,'Sec-WebSocket-Accept')) {
                        $this->outputDecorator->success('Handshake successful: ' . substr($data,$pos,42));
                    }
                    if (strpos($data, 'HTTP/1.1 101') === 0) {
                        $this->outputDecorator->note("sending data: " . json_encode(['type' => $type, 'message' => $message]) );
                        $connection->write($this->encoder->mask($message,$type,true));
                    } else {
                        $decodedMessage = $this->decoder->unmask($data);
                        $this->outputDecorator->success("Received: $decodedMessage") ;
                    }
                });
                $connection->on('close', function () {
                    $this->outputDecorator->error("Connection Closed") ;
                });
            });


        $loop->run();
    }
}
