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

use PHPUnit\Framework\TestCase;
use Sonatra\Bundle\ResourceBundle\DependencyInjection\Compiler\TranslatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests case for translator pass compiler.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class TranslatorPassTest extends TestCase
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
     * @var TranslatorPass
     */
    protected $pass;

    protected function setUp()
    {
        $this->rootDir = sys_get_temp_dir().'/sonatra_resource_bundle_translator_test';
        $this->fs = new Filesystem();
        $this->pass = new TranslatorPass();
    }

    protected function tearDown()
    {
        $this->fs->remove($this->rootDir);
        $this->pass = null;
    }

    public function testProcessWithoutService()
    {
        $container = $this->getContainer();

        $this->assertFalse($container->has('translator.default'));
        $this->pass->process($container);
        $this->assertFalse($container->has('translator.default'));
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

        return $container;
    }
}
