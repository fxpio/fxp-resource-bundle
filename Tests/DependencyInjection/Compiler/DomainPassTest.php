<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Compiler;

use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\TestAppKernel;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Definition;
use Sonatra\Bundle\ResourceBundle\DependencyInjection\Compiler\DomainPass;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests case for domain pass compiler.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainPassTest extends KernelTestCase
{
    protected static $class = 'Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\TestAppKernel';

    /**
     * @var TestAppKernel
     */
    protected static $kernel;

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

    public function testProcessWithDefaultDomainManager()
    {
        $container = $this->buildAndValidateContainerBuilder();

        $dmDef = $container->getDefinition('sonatra_resource.domain_manager');
        $args = $dmDef->getArguments();
        $this->assertCount(5, $args);
        $this->assertCount(1, $args[0]);

        $compiledDef = $args[0][0];

        $this->pass->process($container);

        $dmDef = $container->getDefinition('sonatra_resource.domain_manager');
        $args = $dmDef->getArguments();
        $this->assertCount(5, $args);
        $this->assertCount(1, $args[0]);

        $def = $args[0][0];

        $this->assertNotSame($compiledDef, $def);
    }

    public function testProcessWithCustomDomainManager()
    {
        $className = 'Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo';
        $def = new Definition('Sonatra\Bundle\ResourceBundle\Tests\Fixtures\Domain\CustomDomain', array($className));
        $def->addTag('sonatra_resource.domain');
        $definitions = array(
            'test.custom_domain' => $def,
        );

        $container = $this->buildAndValidateContainerBuilder($definitions);

        $dmDef = $container->getDefinition('sonatra_resource.domain_manager');
        $args = $dmDef->getArguments();
        $this->assertCount(5, $args);
        $this->assertCount(1, $args[0]);

        /* @var Definition $compiledDef */
        $compiledDef = $args[0][0];

        $this->assertNotSame('Sonatra\Bundle\ResourceBundle\Domain\Domain', $compiledDef->getClass());

        $this->pass->process($container);

        $dmDef = $container->getDefinition('sonatra_resource.domain_manager');
        $args = $dmDef->getArguments();
        $this->assertCount(5, $args);
        $this->assertCount(1, $args[0]);

        /* @var Definition $def */
        $def = $args[0][0];

        $this->assertSame('Sonatra\Bundle\ResourceBundle\Tests\Fixtures\Domain\CustomDomain', $def->getClass());
    }

    public function testProcessWithResourceNotManagedByDoctrine()
    {
        $msg = '/The "(\w+)" class is not managed by doctrine object manager/';
        $this->setExpectedExceptionRegExp('Symfony\Component\DependencyInjection\Exception\InvalidArgumentException', $msg);

        $className = 'DateTime';
        $def = new Definition('Sonatra\Bundle\ResourceBundle\Domain\Domain', array($className));
        $def->addTag('sonatra_resource.domain');
        $definitions = array(
            'test.custom_domain' => $def,
        );

        $this->buildAndValidateContainerBuilder($definitions);
    }

    /**
     * @param Definition[] $definitions The definitions
     *
     * @return ContainerBuilder
     */
    protected function buildAndValidateContainerBuilder(array $definitions = array())
    {
        static::$kernel = static::createKernel(array());
        $container = static::$kernel->getContainerBuilderForCompilerPass($definitions);

        $this->assertTrue($container->has('sonatra_resource.domain_manager'));
        $this->assertTrue($container->has('doctrine'));

        return $container;
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
            'kernel.cache_dir'   => $this->rootDir.'/cache',
            'kernel.debug'       => false,
            'kernel.environment' => 'test',
            'kernel.name'        => 'kernel',
            'kernel.root_dir'    => $this->rootDir,
            'kernel.charset'     => 'UTF-8',
            'kernel.bundles'     => $bundles,
        )));

        if (!$empty) {
            $dmDef = new Definition('Sonatra\Bundle\ResourceBundle\Domain\DomainManager');
            $container->setDefinition('sonatra_resource.domain_manager', $dmDef);

            $drDef = new Definition('Doctrine\Common\Persistence\ManagerRegistry');
            $container->setDefinition('doctrine', $drDef);
        }

        return $container;
    }
}
