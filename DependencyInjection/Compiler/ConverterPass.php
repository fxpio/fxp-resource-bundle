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

use Sonatra\Component\Resource\Converter\ConverterInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
            $type = isset($tag[0]['type'])
                ? $this->getRealValue($container, $tag[0]['type'])
                : $this->getType($container, $serviceId);

            $converters[$type] = $container->getDefinition($serviceId);
        }

        return array_values($converters);
    }

    /**
     * Get the real value.
     *
     * @param ContainerBuilder $container The container
     * @param mixed            $value     The value or parameter name
     *
     * @return mixed
     */
    protected function getRealValue(ContainerBuilder $container, $value)
    {
        return 0 === strpos($value, '%')
            ? $container->getParameter(trim($value, '%'))
            : $value;
    }

    /**
     * Get the converter type name.
     *
     * @param ContainerBuilder $container The container builder
     * @param string           $serviceId The service id of converter
     *
     * @return string
     *
     * @throws InvalidConfigurationException When the converter name is not got
     */
    protected function getType(ContainerBuilder $container, $serviceId)
    {
        $def = $container->getDefinition($serviceId);
        $class = $this->getRealValue($container, $def->getClass());
        $interfaces = class_implements($class);
        $error = sprintf('The service id "%s" must be an class implementing the "%s" interface.', $serviceId, ConverterInterface::class);

        if (in_array(ConverterInterface::class, $interfaces)) {
            $ref = new \ReflectionClass($class);
            /* @var ConverterInterface $instance */
            $instance = $ref->newInstanceWithoutConstructor();
            $type = $instance->getName();

            if ($type) {
                return $type;
            }

            $error = sprintf('The service id "%s" must have the "type" parameter in the "sonatra_resource.converter" tag.', $serviceId);
        }

        throw new InvalidConfigurationException($error);
    }
}
