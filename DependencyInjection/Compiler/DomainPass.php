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
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\ExpressionLanguage\Expression;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainPass implements CompilerPassInterface
{
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

        $container->getDefinition('sonatra_resource.domain_manager')->replaceArgument(0, $managers);
        $this->injectDependencies($container, $managers);
    }

    /**
     * Inject the dependencies of domain in each domain.
     *
     * @param ContainerBuilder $container The container service
     * @param array            $managers  The list of definition domain manager
     */
    private function injectDependencies(ContainerBuilder $container, array $managers)
    {
        foreach ($managers as $serviceId => $manager) {
            $def = $container->getDefinition((string) $manager);

            if (0 === count($def->getArguments())) {
                $msg = 'The service "%s" must define the managed class by Doctrine with the first argument';
                throw new InvalidArgumentException(sprintf($msg, $serviceId));
            }

            $pos = $this->getClassPosition($def);
            $def->addMethodCall('setDebug', array('%kernel.debug%'));
            $om = new Expression('service("doctrine").getManagerForClass("'.str_replace('\\', '\\\\', $def->getArgument($pos)).'")');
            $def->addMethodCall('setObjectManager', array($om, '%sonatra_resource.domain.undelete_disable_filters%'));
            $def->addMethodCall('setEventDispatcher', array(new Reference('event_dispatcher')));
            $def->addMethodCall('setObjectFactory', array(new Reference('sonatra_default_value.factory')));
            $def->addMethodCall('setValidator', array(new Reference('validator')));
        }
    }

    /**
     * Get the position of argument of entity class name.
     *
     * @param Definition $def The service definition
     *
     * @return int
     */
    protected function getClassPosition(Definition $def)
    {
        $tag = $def->getTag('sonatra_resource.domain');

        return count($tag) > 0 && isset($tag[0]['class-position'])
            ? $tag[0]['class-position']
            : 0;
    }
}
