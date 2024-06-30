<?php
namespace Snoke\Websocket;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Snoke\Websocket\DependencyInjection\SnokeWebsocketExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
class SnokeWebsocketBundle extends Bundle
{

    public function __construct()
    {
    }


}