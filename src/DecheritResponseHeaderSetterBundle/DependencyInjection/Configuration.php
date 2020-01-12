<?php

namespace Decherit\ResponseHeaderSetterBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('decherit_response_header_setter');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('response_headers')
                    ->children()
                        ->scalarNode('Referrer-Policy')->end()
                        ->scalarNode('Strict-Transport-Security')->end()
                        ->scalarNode('X-Content-Type-Options')->end()
                        ->scalarNode('X-Frame-Options')->end()
                        ->scalarNode('X-XSS-Protection')->end()
                    ->end()
                ->end() // response_headers
            ->end();

        return $treeBuilder;
    }
}
