<?php
namespace Snoke\SoftDelete\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('snoke_websocket');

        $rootNode = $treeBuilder->getRootNode();
        $rootNode
        ->end();

        return $treeBuilder;
    }
}
