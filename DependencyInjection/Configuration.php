<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Bundle\ResourceBundle\DependencyInjection;

use Fxp\Bundle\DefaultValueBundle\FxpDefaultValueBundle;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('fxp_resource');
        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
            ->integerNode('form_handler_default_limit')->defaultNull()->end()
            ->arrayNode('undelete_disable_filters')
            ->defaultValue(['soft_deletable'])
            ->prototype('scalar')->end()
            ->end()
            ->arrayNode('object_factory')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('use_default_value')
            ->defaultValue(class_exists(FxpDefaultValueBundle::class))
            ->end()
            ->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
