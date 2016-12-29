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

            $class = $this->getResolveTarget($container, $def);
            $def->addMethodCall('setDebug', array('%kernel.debug%'));
            $om = new Expression('service("doctrine").getManagerForClass("'.str_replace('\\', '\\\\', $class).'")');
            $def->addMethodCall('setObjectManager', array($om, '%sonatra_resource.domain.undelete_disable_filters%'));
            $def->addMethodCall('setEventDispatcher', array(new Reference('event_dispatcher')));
            $def->addMethodCall('setObjectFactory', array(new Reference('sonatra_default_value.factory')));
            $def->addMethodCall('setValidator', array(new Reference('validator')));
        }
    }

    /**
     * Get the resolve target class.
     *
     * @param ContainerBuilder $container The container
     * @param Definition       $def       The resource domain definition
     *
     * @return string
     */
    private function getResolveTarget(ContainerBuilder $container, Definition $def)
    {
        $classMap = $this->getResolveTargets($container);
        $pos = $this->getClassPosition($def);
        $class = $def->getArgument($pos);

        return isset($classMap[$class]) ? $classMap[$class] : $class;
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

    /**
     * Get the position of argument of entity class name.
     *
     * @param Definition $def The service definition
     *
     * @return int
     */
    private function getClassPosition(Definition $def)
    {
        $tag = $def->getTag('sonatra_resource.domain');

        return count($tag) > 0 && isset($tag[0]['class-position'])
            ? $tag[0]['class-position']
            : 0;
    }
}
