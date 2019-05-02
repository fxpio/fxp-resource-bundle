<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Bundle\ResourceBundle\Tests\DependencyInjection\Compiler;

use Fxp\Bundle\ResourceBundle\DependencyInjection\Compiler\ConverterPass;
use Fxp\Bundle\ResourceBundle\Tests\Fixtures\Converter\CustomConverter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests case for converter pass compiler.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class ConverterPassTest extends TestCase
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

    protected function setUp(): void
    {
        $this->rootDir = sys_get_temp_dir().'/fxp_resource_bundle_converter_test';
        $this->fs = new Filesystem();
        $this->pass = new ConverterPass();
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->rootDir);
        $this->pass = null;
    }

    public function testProcessWithoutService(): void
    {
        $container = $this->getContainer();

        $this->assertFalse($container->has('fxp_resource.converter_registry'));
        $this->pass->process($container);
        $this->assertFalse($container->has('fxp_resource.converter_registry'));
    }

    public function testProcess(): void
    {
        $container = $this->getContainer([
            'FxpResourceBundle' => 'Fxp\\Bundle\\ResourceBundle\\FxpResourceBundle',
        ]);

        $this->assertTrue($container->has('fxp_resource.converter_registry'));
        $this->assertTrue($container->has('fxp_resource.converter.json'));

        $def = $container->getDefinition('fxp_resource.converter_registry');

        $this->assertCount(1, $def->getArguments());
        $this->assertEmpty($def->getArgument(0));

        $this->pass->process($container);

        $this->assertCount(1, $def->getArguments());
        $arg = $def->getArgument(0);
        $this->assertNotEmpty($arg);
        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Definition', $arg[0]);
    }

    public function testProcessWithInvalidInterface(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('The service id "test_invalid_converter_type" must be an class implementing the "Fxp\\Component\\Resource\\Converter\\ConverterInterface" interface.');

        $container = $this->getContainer([
            'FxpResourceBundle' => 'Fxp\\Bundle\\ResourceBundle\\FxpResourceBundle',
        ]);

        $this->assertTrue($container->has('fxp_resource.converter_registry'));

        $def = new Definition('stdClass');
        $def->addTag('fxp_resource.converter');
        $container->setDefinition('test_invalid_converter_type', $def);

        $this->pass->process($container);
    }

    public function testProcessWithInvalidType(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);
        $this->expectExceptionMessage('The service id "test_invalid_converter_type" must have the "type" parameter in the "fxp_resource.converter" tag.');

        $container = $this->getContainer([
            'FxpResourceBundle' => 'Fxp\\Bundle\\ResourceBundle\\FxpResourceBundle',
        ]);

        $this->assertTrue($container->has('fxp_resource.converter_registry'));

        $def = new Definition(CustomConverter::class);
        $def->addTag('fxp_resource.converter');
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
    protected function getContainer(array $bundles = [])
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.cache_dir' => $this->rootDir.'/cache',
            'kernel.debug' => false,
            'kernel.environment' => 'test',
            'kernel.name' => 'kernel',
            'kernel.root_dir' => $this->rootDir,
            'kernel.charset' => 'UTF-8',
            'kernel.bundles' => $bundles,
        ]));

        if (\count($bundles) > 0) {
            $crDef = new Definition('Fxp\Component\Resource\Converter\ConverterRegistry');
            $crDef->addArgument([]);
            $container->setDefinition('fxp_resource.converter_registry', $crDef);

            $jcDef = new Definition('Fxp\Component\Resource\Converter\JsonConverter');
            $jcDef->addTag('fxp_resource.converter');
            $container->setDefinition('fxp_resource.converter.json', $jcDef);
        }

        return $container;
    }
}
