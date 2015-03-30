<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Liip\FunctionalTestBundle\LiipFunctionalTestBundle;
use Sonatra\Bundle\DefaultValueBundle\SonatraDefaultValueBundle;
use Sonatra\Bundle\ResourceBundle\SonatraResourceBundle;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\TestBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\HttpKernel\Kernel;

/**
 * App Test Kernel for functional tests.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class TestAppKernel extends Kernel
{
    /**
     * {@inheritdoc}
     */
    public function registerBundles()
    {
        return array(
            new FrameworkBundle(),
            new DoctrineBundle(),
            new SonatraDefaultValueBundle(),
            new SonatraResourceBundle(),
            new DoctrineFixturesBundle(),
            new LiipFunctionalTestBundle(),
            new TestBundle(),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader)
    {
        $loader->load(__DIR__.DIRECTORY_SEPARATOR.'config.yml');
    }

    /**
     * {@inheritdoc}
     */
    public function getRootDir()
    {
        return sys_get_temp_dir().DIRECTORY_SEPARATOR.'sonatra_resource_bundle';
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir()
    {
        return $this->getRootDir().DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.$this->environment;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir()
    {
        return $this->getRootDir().'/logs';
    }

    /**
     * Get the container builder for the compiler pass tests.
     *
     * @param Definition[] $definitions The definitions
     *
     * @return ContainerBuilder
     */
    public function getContainerBuilderForCompilerPass(array $definitions = array())
    {
        $this->initializeBundles();
        $containerBuilder = $this->buildContainer();

        foreach ($definitions as $id => $definition) {
            $containerBuilder->setDefinition($id, $definition);
        }

        $containerBuilder->compile();

        return $containerBuilder;
    }
}
