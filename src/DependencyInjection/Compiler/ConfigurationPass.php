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

        if (!file_exists($configFile)) {
            $defaultConfig = [
                'snoke_websocket' => [
                    'context' => [],
                ],
            ];

            file_put_contents($configFile, Yaml::dump($defaultConfig, 3));
        }
    }
}
