<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\DependencyInjection\Compiler;

use Sonatra\Bundle\ResourceBundle\DependencyInjection\Compiler\ConverterPass;
use Sonatra\Bundle\ResourceBundle\Tests\Fixtures\Converter\CustomConverter;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests case for converter pass compiler.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ConverterPassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    protected $rootDir;

    /**
     * @var Filesystem
     */
    protected $fs;

    /**
     * @var ConverterPass
     */
    protected $pass;

    protected function setUp()
    {
        $this->rootDir = sys_get_temp_dir().'/sonatra_resource_bundle_converter_test';
        $this->fs = new Filesystem();
        $this->pass = new ConverterPass();
    }

    protected function tearDown()
    {
        $this->fs->remove($this->rootDir);
        $this->pass = null;
    }

    public function testProcessWithoutService()
    {
        $container = $this->getContainer();

        $this->assertFalse($container->has('sonatra_resource.converter_registry'));
        $this->pass->process($container);
        $this->assertFalse($container->has('sonatra_resource.converter_registry'));
    }

    public function testProcess()
    {
        $container = $this->getContainer(array(
            'SonatraResourceBundle' => 'Sonatra\\Bundle\\ResourceBundle\\SonatraResourceBundle',
        ));

        $this->assertTrue($container->has('sonatra_resource.converter_registry'));
        $this->assertTrue($container->has('sonatra_resource.converter.json'));

        $def = $container->getDefinition('sonatra_resource.converter_registry');

        $this->assertCount(1, $def->getArguments());
        $this->assertEmpty($def->getArgument(0));

        $this->pass->process($container);

        $this->assertCount(1, $def->getArguments());
        $arg = $def->getArgument(0);
        $this->assertNotEmpty($arg);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Definition', $arg[0]);
    }

    public function testProcessWithInvalidInterface()
    {
        $msg = 'The service id "test_invalid_converter_type" must have the "type" parameter in the "sonatra_resource.converter" tag.';
        $this->setExpectedException(InvalidConfigurationException::class, $msg);

        $container = $this->getContainer(array(
            'SonatraResourceBundle' => 'Sonatra\\Bundle\\ResourceBundle\\SonatraResourceBundle',
        ));

        $this->assertTrue($container->has('sonatra_resource.converter_registry'));

        $def = new Definition('stdClass');
        $def->addTag('sonatra_resource.converter');
        $container->setDefinition('test_invalid_converter_type', $def);

        $this->pass->process($container);
    }

    public function testProcessWithInvalidType()
    {
        $msg = 'The service id "test_invalid_converter_type" must have the "type" parameter in the "sonatra_resource.converter" tag.';
        $this->setExpectedException(InvalidConfigurationException::class, $msg);

        $container = $this->getContainer(array(
            'SonatraResourceBundle' => 'Sonatra\\Bundle\\ResourceBundle\\SonatraResourceBundle',
        ));

        $this->assertTrue($container->has('sonatra_resource.converter_registry'));

        $def = new Definition(CustomConverter::class);
        $def->addTag('sonatra_resource.converter');
        $container->setDefinition('test_invalid_converter_type', $def);

        $this->pass->process($container);
    }

    /**
     * Gets the container.
     *
     * @param array $bundles
     *
     * @return ContainerBuilder
     */
    protected function getContainer(array $bundles = array())
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.cache_dir' => $this->rootDir.'/cache',
            'kernel.debug' => false,
            'kernel.environment' => 'test',
            'kernel.name' => 'kernel',
            'kernel.root_dir' => $this->rootDir,
            'kernel.charset' => 'UTF-8',
            'kernel.bundles' => $bundles,
        )));

        if (count($bundles) > 0) {
            $crDef = new Definition('Sonatra\Bundle\ResourceBundle\Converter\ConverterRegistry');
            $crDef->addArgument(array());
            $container->setDefinition('sonatra_resource.converter_registry', $crDef);

            $jcDef = new Definition('Sonatra\Bundle\ResourceBundle\Converter\JsonConverter');
            $jcDef->addTag('sonatra_resource.converter');
            $container->setDefinition('sonatra_resource.converter.json', $jcDef);
        }

        return $container;
    }
}
