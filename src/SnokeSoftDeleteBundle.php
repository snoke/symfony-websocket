<?php
namespace Snoke\Websocket;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Snoke\Websocket\DependencyInjection\SnokeWebsocketExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
class SnokeWebsocketBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new SnokeWebsocketExtension();
    }
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }

}