<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Functional\Domain;

use Doctrine\Common\Persistence\ObjectManager;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Sonatra\Bundle\DefaultValueBundle\DefaultValue\ObjectFactoryInterface;
use Sonatra\Bundle\ResourceBundle\Domain\Domain;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\TestAppKernel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Functional tests for Domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainTest extends WebTestCase
{
    protected static function createKernel(array $options = array())
    {
        return new TestAppKernel('test', true);
    }

    public function testMappingException()
    {
        $class = 'DateTime';
        $msg = 'The "'.$class.'" class is not managed by doctrine object manager';
        $this->setExpectedException('Sonatra\Bundle\ResourceBundle\Exception\InvalidConfigurationException', $msg);

        $this->createDomain($class);
    }

    public function testGetRepository()
    {
        $domain = $this->createDomain();

        $this->assertInstanceOf('Doctrine\Common\Persistence\ObjectRepository', $domain->getRepository());
    }

    public function testGetEventPrefix()
    {
        $domain = $this->createDomain();

        $valid = 'sonatra_bundle_resource_bundle_tests_functional_fixture_bundle_test_bundle_entity_foo';
        $this->assertSame($valid, $domain->getEventPrefix());
    }

    public function testNewInstance()
    {
        $class = 'Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo';
        $domain = $this->createDomain($class);
        $resource1 = $domain->newInstance();
        $resource2 = $this->getContainer()->get('sonatra_default_value.factory')->create($class);

        $this->assertEquals($resource2, $resource1);
    }

    /**
     * Create resource domain.
     *
     * @param string $class
     *
     * @return Domain
     */
    protected function createDomain($class = 'Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo')
    {
        $container = $this->getContainer();
        /* @var ObjectManager $om */
        $om = $container->get('doctrine.orm.entity_manager');
        /* @var EventDispatcherInterface $ed */
        $ed = $container->get('event_dispatcher');
        /* @var ObjectFactoryInterface $of */
        $of = $container->get('sonatra_default_value.factory');
        /* @var ValidatorInterface $val */
        $val = $container->get('validator');

        $domain = new Domain($class);
        $domain->setObjectManager($om);
        $domain->setEventDispatcher($ed);
        $domain->setObjectFactory($of);
        $domain->setValidator($val);

        return $domain;
    }
}
