<?php

namespace Versh\SphinxBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private static $allowedTypes = ['int'];

    private function addConnectionSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('connection')
                    ->children()
                        ->scalarNode('host')
                            ->defaultValue('localhost')
                        ->end()
                        ->scalarNode('port')
                            ->defaultValue('9306')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return \Symfony\Component\Config\Definition\Builder\TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root('versh_sphinx', 'array');

        $this->addConnectionSection($rootNode);
        $this->addIndexesSection($rootNode);

        return $treeBuilder;
    }

    private function addIndexesSection(ArrayNodeDefinition $rootNode)
    {
        $rootNode
            ->children()
                ->arrayNode('indexes')
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('class')->isRequired()->end()
                            ->scalarNode('method')->defaultValue('createQueryBuilder')->end()
                            ->arrayNode('fields')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('path')->isRequired()->end()
                                        ->booleanNode('stored')->defaultFalse()->end()
                                    ->end()
                                ->end()
                            ->end()
                            ->arrayNode('attributes')
                                ->arrayPrototype()
                                    ->children()
                                        ->scalarNode('path')
                                            ->isRequired()
                                        ->end()
                                        ->enumNode('type')
                                            ->values(self::$allowedTypes)
                                        ->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }
}
