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
use Doctrine\ORM\EntityManagerInterface;
use Liip\FunctionalTestBundle\Test\WebTestCase;
use Sonatra\Bundle\DefaultValueBundle\DefaultValue\ObjectFactoryInterface;
use Sonatra\Bundle\ResourceBundle\Domain\Domain;
use Sonatra\Bundle\ResourceBundle\Domain\DomainInterface;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\TestAppKernel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Abstract class for Functional tests for Domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
abstract class AbstractDomainTest extends WebTestCase
{
    protected static function createKernel(array $options = array())
    {
        return new TestAppKernel('test', true);
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
        $domain->setDebug(true);
        $domain->setObjectManager($om);
        $domain->setEventDispatcher($ed);
        $domain->setObjectFactory($of);
        $domain->setValidator($val);

        return $domain;
    }

    /**
     * Insert object in database.
     *
     * @param DomainInterface $domain
     *
     * @return Foo
     */
    protected function insertResource(DomainInterface $domain)
    {
        return current($this->insertResources($domain, 1));
    }

    /**
     * Insert objects in database.
     *
     * @param DomainInterface $domain
     * @param int             $size
     *
     * @return Foo[]
     */
    protected function insertResources(DomainInterface $domain, $size)
    {
        $this->loadFixtures(array());

        /* @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $objects = array();

        for ($i = 0; $i < $size; $i++) {
            /* @var Foo $foo */
            $foo = $domain->newInstance();
            $foo->setName('Bar '.($i+1));
            $foo->setDetail('Detail '.($i+1));
            $em->persist($foo);
            $objects[] = $foo;
        }

        $em->flush();

        return $objects;
    }
}
