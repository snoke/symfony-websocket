<?php

namespace Snoke\Websocket\Command;

use React\EventLoop\Factory;
use React\Socket\Server;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use React\Socket\ConnectionInterface;


/**
 * ServerStartCommand
 *
 * starts the websocket server
 */
#[AsCommand(
    name: 'server:start',
    description: 'Starts the Websocket Server',
)]
class ServerStartCommand extends Command
{
    public const WS_PORT = 8080;
    public const WSS_PORT = 8443;

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addArgument('port', InputArgument::OPTIONAL, 'custom Port')
            ->addOption(
                'ssl',
                null,
                InputOption::VALUE_NONE,
                'use SSL (WSS Protocol)'
            );
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $io = new SymfonyStyle($input, $output);


        $this->runWsServer();

        $io->success('Websocket Server started on Port ' . $this->port);

        return Command::SUCCESS;
    }

    public function runWsServer() {
        $loop = Factory::create();
        $socket = new Server('0.0.0.0:8080', $loop);

        echo "WebSocket server started at ws://localhost:8080\n";

        $socket->on('connection', function (ConnectionInterface $connection) {
            echo "New connection\n";

            $connection->on('data', function ($data) use ($connection) {
                $message = json_decode($data, true);

                if ($message && isset($message['type'])) {
                    switch ($message['type']) {
                        case 'message':
                            $this->broadcastMessage($connection, $message['payload']);
                            break;
                        default:
                            echo "Unknown message type: " . $message['type'] . "\n";
                            break;
                    }
                }
            });

            $connection->on('close', function () {
                echo "Connection closed\n";
            });
        });

        $loop->run();
    }
    public function broadcastMessage(ConnectionInterface $from, $message)
    {
            global $socket;
            foreach ($socket->getConnections() as $connection) {
                if ($connection !== $from) {
                    $connection->write(json_encode([
                        'type' => 'message',
                        'payload' => $message,
                    ]));
                }
            }
    }
}