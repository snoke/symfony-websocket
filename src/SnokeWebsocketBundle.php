<?php
namespace Snoke\Websocket;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Snoke\Websocket\DependencyInjection\SnokeWebsocketExtension;

use Symfony\Component\DependencyInjection\ContainerBuilder;
class SnokeWebsocketBundle extends Bundle
{

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new DependencyInjection\SnokeWebsocketExtension();
        }

        return $this->extension;
    }

}