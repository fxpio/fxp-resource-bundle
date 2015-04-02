<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ConverterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sonatra_resource.converter_registry')) {
            return;
        }

        $converters = $this->findConverters($container);
        $container->getDefinition('sonatra_resource.converter_registry')->replaceArgument(0, $converters);
    }

    /**
     * Find the converters.
     *
     * @param ContainerBuilder $container The container service
     *
     * @return Definition[] The converter definitions
     */
    private function findConverters(ContainerBuilder $container)
    {
        $converters = array();

        foreach ($container->findTaggedServiceIds('sonatra_resource.converter') as $serviceId => $tag) {
            $name = $container->get($serviceId)->getName();
            $converters[$name] = $container->getDefinition($serviceId);
        }

        return array_values($converters);
    }
}
