<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Bundle\ResourceBundle\Tests\DependencyInjection;

use Fxp\Bundle\ResourceBundle\DependencyInjection\FxpResourceExtension;
use Fxp\Bundle\ResourceBundle\FxpResourceBundle;
use Fxp\Component\Resource\Object\DefaultValueObjectFactory;
use Fxp\Component\Resource\Object\DoctrineObjectFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Tests case for Extension.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class FxpResourceExtensionTest extends TestCase
{
    public function testExtensionExist(): void
    {
        $container = $this->createContainer();

        $this->assertTrue($container->hasExtension('fxp_resource'));
    }

    public function testExtensionLoader(): void
    {
        $container = $this->createContainer();

        $this->assertTrue($container->hasDefinition('fxp_resource.converter_registry'));
        $this->assertTrue($container->hasDefinition('fxp_resource.domain_manager'));
        $this->assertTrue($container->hasDefinition('fxp_resource.form_handler'));

        $def = $container->getDefinition('fxp_resource.object_factory');
        $this->assertSame(DefaultValueObjectFactory::class, $def->getClass());
    }

    public function testExtensionDisableDefaultValue(): void
    {
        $container = $this->createContainer([
            [
                'object_factory' => [
                    'use_default_value' => false,
                ],
            ],
        ]);

        $this->assertTrue($container->hasDefinition('fxp_resource.converter_registry'));
        $this->assertTrue($container->hasDefinition('fxp_resource.domain_manager'));
        $this->assertTrue($container->hasDefinition('fxp_resource.form_handler'));
        $this->assertTrue($container->hasDefinition('fxp_resource.object_factory'));

        $def = $container->getDefinition('fxp_resource.object_factory');
        $this->assertSame(DoctrineObjectFactory::class, $def->getClass());
    }

    protected function createContainer(array $configs = [])
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.bundles' => [
                'FrameworkBundle' => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle',
                'FxpResourceBundle' => 'Fxp\\Bundle\\ResourceBundle\\FxpResourceBundle',
            ],
            'kernel.bundles_metadata' => [],
            'kernel.cache_dir' => sys_get_temp_dir().'/fxp_resource_bundle',
            'kernel.debug' => false,
            'kernel.environment' => 'test',
            'kernel.name' => 'kernel',
            'kernel.root_dir' => sys_get_temp_dir().'/fxp_resource_bundle',
            'kernel.project_dir' => sys_get_temp_dir().'/fxp_resource_bundle',
            'kernel.charset' => 'UTF-8',
        ]));

        $sfExt = new FrameworkExtension();
        $extension = new FxpResourceExtension();

        $container->registerExtension($sfExt);
        $container->registerExtension($extension);

        $sfExt->load([['form' => true]], $container);
        $extension->load($configs, $container);

        $bundle = new FxpResourceBundle();
        $bundle->build($container);

        $container->getCompilerPassConfig()->setOptimizationPasses([]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();

        return $container;
    }
}
