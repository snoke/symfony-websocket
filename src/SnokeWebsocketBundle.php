<?php
namespace Snoke\Websocket;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
class SnokeWebsocketBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
    }

}