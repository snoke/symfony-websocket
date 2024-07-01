<?php
namespace Snoke\Websocket;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
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