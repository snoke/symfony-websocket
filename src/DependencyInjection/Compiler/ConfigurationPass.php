<?php
namespace Snoke\Websocket\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Yaml\Yaml;

class ConfigurationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $configFile = $container->getParameter('kernel.project_dir') . '/config/packages/snoke_websocket.yaml';

        $bundleConfigFile = __DIR__ . '/../../Resources/config/snoke_websocket.yaml';

        if (!file_exists($configFile)) {
            $defaultConfig = file_get_contents($bundleConfigFile);
            file_put_contents($configFile, $defaultConfig);
        }
    }
}
