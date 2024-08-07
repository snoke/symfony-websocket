<?php
namespace Snoke\Websocket;

use Snoke\Websocket\DependencyInjection\Compiler\ConfigurationPass;
use Snoke\Websocket\DependencyInjection\Compiler\UninstallPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new ConfigurationPass());
        $container->addCompilerPass(new UninstallPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }
}