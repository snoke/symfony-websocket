<?php

namespace Snoke\Websocket\Command;

use Psr\Log\LoggerInterface;
use Snoke\Websocket\Service\WebsocketServer;
use Snoke\Websocket\Service\Decoder;
use Snoke\Websocket\Service\Encoder;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(
    name: 'websocket:start',
    description: 'Starts a websocket server',
)]
class WebsocketStartCommand extends Command
{
    private SymfonyStyle $outputDecorator;
    private WebsocketServer $websocketServer;

    public function __construct(Encoder $encoder, Decoder $decoder, LoggerInterface $logger, EventDispatcherInterface $eventDispatcher, array $channels = ['main'])
    {
        $this->websocketServer = new WebsocketServer($encoder, $decoder, $logger, $eventDispatcher, $channels);
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('debug', null, InputOption::VALUE_OPTIONAL, 'debug', 'true')
            ->addOption('ip', null, InputOption::VALUE_OPTIONAL, 'custom ip', '0.0.0.0')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'custom port', '8080');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $outputDecorator = new SymfonyStyle($input, $output);
        $debug = filter_var($input->getOption('debug'), FILTER_VALIDATE_BOOLEAN);
        $ip = $input->getOption('ip');
        $port = $input->getOption('port');

        $this->websocketServer->start($ip, $port, $debug, $outputDecorator);


        return Command::SUCCESS;
    }
}
