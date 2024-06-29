<?php

namespace Snoke\Websocket\Command;

use Snoke\Websocket\Server;
use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


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
    protected AppServer $server;
    public const WS_PORT = 8080;
    public const WSS_PORT = 8443;

    private ?int $port;

    /**
     * @param AppServer $server
     */
    public function __construct(Server $server)
    {
        parent::__construct();
        $this->server = $server;
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
     * @param HttpServer $httpServer
     * @return IoServer
     */
    private function createWsServer(HttpServer $httpServer): IoServer
    {
        $this->port = $this->port ?: self::WS_PORT;
        return IoServer::factory(
            $httpServer,
            $this->port
        );
    }

    /**
     * @param HttpServer $httpServer
     * @return IoServer
     */
    private function createWssServer(HttpServer $httpServer): IoServer
    {

        $this->port = $this->port ?: self::WSS_PORT;
        $loop = \React\EventLoop\Factory::create();
        $server = new \React\Socket\Server('0.0.0.0:' . $this->port, $loop);
        $dir =  __DIR__ . '/../..';
        $secureServer = new \React\Socket\SecureServer($server, $loop, [
            'local_cert' => $dir . '/config/ssl/certificate.crt',
            'local_pk' => $dir . '/config/ssl/private.key',
            'verify_peer' => false,
        ]);

        return new IoServer($httpServer, $secureServer, $loop);

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->port = $input->getArgument('port');

        $this->server->setInterface($input, $output);

        $io = new SymfonyStyle($input, $output);

        $httpServer = new HttpServer(new WsServer($this->server));

        if ($input->getOption('ssl')) {
            $server = $this->createWssServer($httpServer);
            $io->success('Secured Websocket Server started on Port ' . $this->port);

        } else {
            $server = $this->createWsServer($httpServer);
            $io->success('Websocket Server started on Port ' . $this->port);
        }


        $server->run();

        return Command::SUCCESS;
    }
}