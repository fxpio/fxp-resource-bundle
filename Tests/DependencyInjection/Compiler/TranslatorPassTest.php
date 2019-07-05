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

use Fxp\Bundle\ResourceBundle\DependencyInjection\Compiler\TranslatorPass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Tests case for translator pass compiler.
 *
 * @author François Pluchino <francois.pluchino@gmail.com>
 *
 * @internal
 */
final class TranslatorPassTest extends TestCase
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

    protected function setUp(): void
    {
        $this->rootDir = sys_get_temp_dir().'/fxp_resource_bundle_translator_test';
        $this->fs = new Filesystem();
        $this->pass = new TranslatorPass();
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->rootDir);
        $this->pass = null;
    }

    public function testProcessWithoutService(): void
    {
        $container = $this->getContainer();

        static::assertFalse($container->has('translator.default'));
        $this->pass->process($container);
        static::assertFalse($container->has('translator.default'));
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
        return new ContainerBuilder(new ParameterBag([
            'kernel.cache_dir' => $this->rootDir.'/cache',
            'kernel.debug' => false,
            'kernel.environment' => 'test',
            'kernel.name' => 'kernel',
            'kernel.root_dir' => $this->rootDir,
            'kernel.charset' => 'UTF-8',
            'kernel.bundles' => $bundles,
        ]));
    }
}
