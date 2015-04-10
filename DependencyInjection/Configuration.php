<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('sonatra_resource');
        $rootNode
            ->addDefaultsIfNotSet()
            ->children()
                ->integerNode('form_handler_default_limit')->defaultNull()->end()
                ->arrayNode('undelete_disable_filters')
                    ->defaultValue(array('soft_deletable'))
                    ->prototype('scalar')->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
