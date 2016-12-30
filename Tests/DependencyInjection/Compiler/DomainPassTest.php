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

use Sonatra\Bundle\ResourceBundle\Tests\Fixtures\Domain\CustomDomain;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Definition;
use Sonatra\Bundle\ResourceBundle\DependencyInjection\Compiler\DomainPass;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests case for domain pass compiler.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainPassTest extends \PHPUnit_Framework_TestCase
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
     * @var DomainPass
     */
    protected $pass;

    protected function setUp()
    {
        $this->rootDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sonatra_resource_bundle_compiler';
        $this->fs = new Filesystem();
        $this->pass = new DomainPass();
    }

    protected function tearDown()
    {
        $this->fs->remove($this->rootDir);
        $this->pass = null;
    }

    public function getBundles()
    {
        return array(
            array(array()),
            array(array(
                'SonatraResourceBundle' => 'Sonatra\\Bundle\\ResourceBundle\\SonatraResourceBundle',
            )),
        );
    }

    /**
     * @dataProvider getBundles
     *
     * @param array $bundles
     */
    public function testProcessWithoutService(array $bundles)
    {
        $container = $this->getContainer($bundles, true);

        $this->assertFalse($container->hasDefinition('sonatra_resource.domain_manager'));
        $this->assertFalse($container->hasDefinition('doctrine'));

        $this->pass->process($container);
    }

    public function testProcessWithCustomDomainManager()
    {
        $container = $this->getContainer(array(
            'SonatraResourceBundle' => 'Sonatra\\Bundle\\ResourceBundle\\SonatraResourceBundle',
        ));

        $this->assertTrue($container->has('sonatra_resource.domain_manager'));

        $def = new Definition(CustomDomain::class);
        $def->addTag('sonatra_resource.domain');
        $def->setArguments(array(
            \stdClass::class,
        ));
        $container->setDefinition('test_valid_custom_domain', $def);

        $this->pass->process($container);

        $this->assertCount(0, $def->getMethodCalls());
    }

    public function testProcessWithDoctrineResolveTargets()
    {
        $container = $this->getContainer(array(
            'SonatraResourceBundle' => 'Sonatra\\Bundle\\ResourceBundle\\SonatraResourceBundle',
        ));

        $this->assertTrue($container->hasDefinition('sonatra_resource.domain_manager'));
        $this->assertFalse($container->hasDefinition('doctrine.orm.listeners.resolve_target_entity'));

        $rteDef = new Definition();
        $rteDef->addMethodCall('addResolveTargetEntity', array('stdClassInterface', \stdClass::class));
        $container->setDefinition('doctrine.orm.listeners.resolve_target_entity', $rteDef);

        $managerDef = $container->getDefinition('sonatra_resource.domain_manager');
        $this->assertCount(0, $managerDef->getMethodCalls());

        $this->pass->process($container);
        $calls = $managerDef->getMethodCalls();
        $this->assertCount(1, $managerDef->getMethodCalls());
        $this->assertSame('addResolveTargets', $calls[0][0]);
    }

    /**
     * Gets the container.
     *
     * @param array $bundles
     * @param bool  $empty
     *
     * @return ContainerBuilder
     */
    protected function getContainer(array $bundles, $empty = false)
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

        if (!$empty) {
            $dmDef = new Definition('Sonatra\Component\Resource\Domain\DomainManager');
            $dmDef->setArguments(array(
                array(),
                new Reference('sonatra_resource.domain_factory'),
            ));
            $container->setDefinition('sonatra_resource.domain_manager', $dmDef);

            $drDef = new Definition('Doctrine\Common\Persistence\ManagerRegistry');
            $container->setDefinition('doctrine', $drDef);
        }

        return $container;
    }
}
