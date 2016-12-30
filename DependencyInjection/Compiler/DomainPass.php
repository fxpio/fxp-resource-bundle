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
use Symfony\Component\DependencyInjection\Reference;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainPass implements CompilerPassInterface
{
    /**
     * @var array|null
     */
    private $resolveTargets;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('sonatra_resource.domain_manager')
                || !$container->hasDefinition('doctrine')) {
            return;
        }

        $managers = array();

        foreach ($container->findTaggedServiceIds('sonatra_resource.domain') as $serviceId => $tag) {
            $managers[$serviceId] = new Reference($serviceId);
        }

        $container->getDefinition('sonatra_resource.domain_manager')
            ->replaceArgument(0, $managers)
            ->addMethodCall('addResolveTargets', array($this->getResolveTargets($container)));
    }

    /**
     * Get the resolve target classes.
     *
     * @param ContainerBuilder $container The container
     *
     * @return array
     */
    private function getResolveTargets(ContainerBuilder $container)
    {
        if (null === $this->resolveTargets) {
            $this->resolveTargets = array();

            if ($container->hasDefinition('doctrine.orm.listeners.resolve_target_entity')) {
                $def = $container->getDefinition('doctrine.orm.listeners.resolve_target_entity');

                foreach ($def->getMethodCalls() as $call) {
                    if ('addResolveTargetEntity' === $call[0]) {
                        $this->resolveTargets[$call[1][0]] = $call[1][1];
                    }
                }
            }
        }

        return $this->resolveTargets;
    }
}
