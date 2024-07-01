<?php
namespace Snoke\Websocket\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('snoke_websocket');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->children()
            ->arrayNode('context')->children()
                ->arrayNode('tls')->children()
                    ->scalarNode('local_cert')->end()
                    ->scalarNode('local_pk')->end()
                    ->booleanNode('allow_self_signed')->end()
                    ->booleanNode('verify_peer')->end()
                ->end()
            ->end()
            ->end()
            ->end()
            ->end();


        return $treeBuilder;
    }
}
