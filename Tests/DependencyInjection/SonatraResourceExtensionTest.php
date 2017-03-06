<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\DependencyInjection;

use Sonatra\Bundle\ResourceBundle\DependencyInjection\SonatraResourceExtension;
use Sonatra\Bundle\ResourceBundle\SonatraResourceBundle;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * Tests case for Extension.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class SonatraResourceExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function testExtensionExist()
    {
        $container = $this->createContainer();

        $this->assertTrue($container->hasExtension('sonatra_resource'));
    }

    public function testExtensionLoader()
    {
        $container = $this->createContainer();

        $this->assertTrue($container->hasDefinition('sonatra_resource.converter_registry'));
        $this->assertTrue($container->hasDefinition('sonatra_resource.domain_manager'));
        $this->assertTrue($container->hasDefinition('sonatra_resource.form_handler'));
    }

    protected function createContainer(array $configs = array())
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.bundles' => array(
                'FrameworkBundle' => 'Symfony\\Bundle\\FrameworkBundle\\FrameworkBundle',
                'SonatraResourceBundle' => 'Sonatra\\Bundle\\ResourceBundle\\SonatraResourceBundle',
            ),
            'kernel.bundles_metadata' => array(),
            'kernel.cache_dir' => sys_get_temp_dir().'/sonatra_resource_bundle',
            'kernel.debug' => false,
            'kernel.environment' => 'test',
            'kernel.name' => 'kernel',
            'kernel.root_dir' => sys_get_temp_dir().'/sonatra_resource_bundle',
            'kernel.charset' => 'UTF-8',
        )));

        $sfExt = new FrameworkExtension();
        $extension = new SonatraResourceExtension();

        $container->registerExtension($sfExt);
        $container->registerExtension($extension);

        $sfExt->load(array(array('form' => true)), $container);
        $extension->load($configs, $container);

        $bundle = new SonatraResourceBundle();
        $bundle->build($container);

        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        return $container;
    }
}
