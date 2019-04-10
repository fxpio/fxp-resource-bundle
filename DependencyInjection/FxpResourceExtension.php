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

use Fxp\Component\Resource\Object\DefaultValueObjectFactory;
use Fxp\Component\Resource\Object\DoctrineObjectFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class FxpResourceExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('domain.xml');

        if (class_exists(Form::class)) {
            $loader->load('converter.xml');
            $loader->load('handler.xml');
            $container->setParameter('fxp_resource.form_handler_default_limit', $config['form_handler_default_limit']);
        }

        $container->setParameter('fxp_resource.domain.undelete_disable_filters', $config['undelete_disable_filters']);

        $container->setDefinition('fxp_resource.object_factory', $this->getObjectFactoryDefinition($config));
    }

    /**
     * Get the object factory definition.
     *
     * @param array $config The config
     *
     * @return Definition
     */
    private function getObjectFactoryDefinition(array $config)
    {
        if ($config['object_factory']['use_default_value']) {
            $class = DefaultValueObjectFactory::class;
            $args = [new Reference('fxp_default_value.factory')];
        } else {
            $class = DoctrineObjectFactory::class;
            $args = [new Reference('doctrine.orm.entity_manager')];
        }

        return new Definition($class, $args);
    }
}
