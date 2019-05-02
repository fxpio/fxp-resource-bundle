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

use Doctrine\Common\Persistence\ManagerRegistry;
use Fxp\Bundle\ResourceBundle\DependencyInjection\Compiler\DomainPass;
use Fxp\Bundle\ResourceBundle\Tests\Fixtures\Domain\CustomDomain;
use Fxp\Component\Resource\Domain\DomainFactory;
use Fxp\Component\Resource\Domain\DomainManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests case for domain pass compiler.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class DomainPassTest extends TestCase
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

    protected function setUp(): void
    {
        $this->rootDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'fxp_resource_bundle_compiler';
        $this->fs = new Filesystem();
        $this->pass = new DomainPass();
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->rootDir);
        $this->pass = null;
    }

    public function getBundles()
    {
        return [
            [[]],
            [[
                'FxpResourceBundle' => 'Fxp\\Bundle\\ResourceBundle\\FxpResourceBundle',
            ]],
        ];
    }

    /**
     * @dataProvider getBundles
     *
     * @param array $bundles
     */
    public function testProcessWithoutService(array $bundles): void
    {
        $container = $this->getContainer($bundles, true);

        $this->assertFalse($container->hasDefinition('fxp_resource.domain_manager'));
        $this->assertFalse($container->hasDefinition('doctrine'));

        $this->pass->process($container);
    }

    public function testProcessWithCustomDomainManager(): void
    {
        $container = $this->getContainer([
            'FxpResourceBundle' => 'Fxp\\Bundle\\ResourceBundle\\FxpResourceBundle',
        ]);

        $this->assertTrue($container->has('fxp_resource.domain_manager'));

        $def = new Definition(CustomDomain::class);
        $def->addTag('fxp_resource.domain');
        $def->setArguments([
            \stdClass::class,
        ]);
        $container->setDefinition('test_valid_custom_domain', $def);

        $this->pass->process($container);

        $this->assertCount(0, $def->getMethodCalls());
    }

    public function testProcessWithDoctrineResolveTargets(): void
    {
        $container = $this->getContainer([
            'FxpResourceBundle' => 'Fxp\\Bundle\\ResourceBundle\\FxpResourceBundle',
        ]);

        $this->assertTrue($container->hasDefinition('fxp_resource.domain_manager'));
        $this->assertFalse($container->hasDefinition('doctrine.orm.listeners.resolve_target_entity'));

        $rteDef = new Definition();
        $rteDef->addMethodCall('addResolveTargetEntity', ['stdClassInterface', \stdClass::class]);
        $container->setDefinition('doctrine.orm.listeners.resolve_target_entity', $rteDef);

        $factoryDef = $container->getDefinition('fxp_resource.domain_factory');
        $this->assertCount(0, $factoryDef->getMethodCalls());

        $this->pass->process($container);
        $calls = $factoryDef->getMethodCalls();
        $this->assertCount(1, $factoryDef->getMethodCalls());
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
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.cache_dir' => $this->rootDir.'/cache',
            'kernel.debug' => false,
            'kernel.environment' => 'test',
            'kernel.name' => 'kernel',
            'kernel.root_dir' => $this->rootDir,
            'kernel.charset' => 'UTF-8',
            'kernel.bundles' => $bundles,
        ]));

        if (!$empty) {
            $dmDef = new Definition(DomainManager::class);
            $dmDef->setArguments([
                [],
                new Reference('fxp_resource.domain_factory'),
            ]);
            $container->setDefinition('fxp_resource.domain_manager', $dmDef);

            $dfDef = new Definition(DomainFactory::class);
            $dfDef->setArguments([
                new Reference('doctrine'),
                new Reference('event_dispatcher'),
                new Reference('fxp_default_value.factory'),
                new Reference('validator'),
            ]);
            $container->setDefinition('fxp_resource.domain_factory', $dfDef);

            $drDef = new Definition(ManagerRegistry::class);
            $container->setDefinition('doctrine', $drDef);
        }

        return $container;
    }
}
