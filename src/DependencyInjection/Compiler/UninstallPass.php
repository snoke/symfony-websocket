<?php
namespace Snoke\Websocket\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
class UninstallPass implements CompilerPassInterface
{

    public function process(ContainerBuilder $container)
    {
        $configFile = $container->getParameter('kernel.project_dir') . '/config/packages/snoke_websocket.yaml';

        if (file_exists($configFile)) {
            unlink($configFile);
        }
    }

}
