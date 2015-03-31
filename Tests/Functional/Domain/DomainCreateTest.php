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

use Sonatra\Bundle\ResourceBundle\Domain\DomainInterface;
use Sonatra\Bundle\ResourceBundle\Event\ResourceEvent;
use Sonatra\Bundle\ResourceBundle\ResourceEvents;
use Sonatra\Bundle\ResourceBundle\ResourceListStatutes;
use Sonatra\Bundle\ResourceBundle\ResourceStatutes;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Functional tests for create methods of Domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainCreateTest extends AbstractDomainTest
{
    public function testCreateWithErrorValidation()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo */
        $foo = $domain->newInstance();

        $this->runTestCreateException($domain, $foo, '/This value should not be blank./');
    }

    public function testCreateWithErrorDatabase()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo */
        $foo = $domain->newInstance();
        $foo->setName('Bar');

        $this->runTestCreateException($domain, $foo, '/Database error code "(\d+)"/');
    }

    protected function runTestCreateException(DomainInterface $domain, $object, $errorMessage)
    {
        $this->loadFixtures(array());

        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent) {
            $preEvent = true;
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent) {
            $postEvent = true;
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resource = $domain->create($object);
        $this->assertCount(1, $resource->getErrors());
        $this->assertRegExp($errorMessage, $resource->getErrors()->get(0)->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(0, $domain->getRepository()->findAll());
    }

    public function testCreate()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo */
        $foo = $domain->newInstance();
        $foo->setName('Bar');
        $foo->setDetail('Detail');

        $this->loadFixtures(array());

        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent) {
            $preEvent = true;
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent) {
            $postEvent = true;
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::CREATED, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resource = $domain->create($foo);
        $this->assertCount(0, $resource->getErrors());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(1, $domain->getRepository()->findAll());
    }

    public function testCreatesWithErrorValidation()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();

        $this->runTestCreatesException($domain, array($foo1, $foo2), '/This value should not be blank./', true);
    }

    public function testCreatesWithErrorDatabase()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        $foo1->setName('Bar');
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar');

        $this->runTestCreatesException($domain, array($foo1, $foo2), '/Database error code "(\d+)"/', false);
    }

    protected function runTestCreatesException(DomainInterface $domain, array $objects, $errorMessage, $autoCommit = false)
    {
        $this->loadFixtures(array());

        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent) {
            $preEvent = true;
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent, $autoCommit) {
            $postEvent = true;
            $resources = $e->getResources();
            $this->assertCount(2, $resources);
            $this->assertSame(ResourceStatutes::ERROR, $resources[0]->getStatus());
            $this->assertSame($autoCommit ? ResourceStatutes::CANCELED
                : ResourceStatutes::ERROR, $resources[1]->getStatus());
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($objects);
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface', $resources);

        /* @var ConstraintViolationListInterface $errors */
        $errors = $autoCommit
            ? $resources->getChildrenErrors()
            : $resources->getErrors();
        $this->assertCount(1, $errors);
        $this->assertRegExp($errorMessage, $errors->get(0)->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(0, $domain->getRepository()->findAll());
        $this->assertSame($autoCommit ? ResourceListStatutes::MIXED
            : ResourceListStatutes::ERROR, $resources->getStatus());
    }

    public function testCreates()
    {
        $this->runTestCreates(false);
    }

    public function testCreatesAutoCommitWithErrorValidationAndErrorDatabase()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar');

        $objects = array($foo1, $foo2);

        $this->loadFixtures(array());

        $preEvent = false;
        $postEvent = false;
        $dispatcher = $this->getContainer()->get('event_dispatcher');

        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::PRE_CREATES, function (ResourceEvent $e) use (&$preEvent) {
            $preEvent = true;
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::PENDING, $resource->getStatus());
            }
        });
        $dispatcher->addListener($domain->getEventPrefix().ResourceEvents::POST_CREATES, function (ResourceEvent $e) use (&$postEvent) {
            $postEvent = true;
            foreach ($e->getResources() as $resource) {
                $this->assertSame(ResourceStatutes::ERROR, $resource->getStatus());
            }
        });

        $this->assertCount(0, $domain->getRepository()->findAll());

        $resources = $domain->creates($objects, true);
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface', $resources);

        $this->assertCount(2, $resources->getChildrenErrors());
        $this->assertRegExp('/This value should not be blank./', $resources->getChildrenErrors()->get(0)->getMessage());
        $this->assertRegExp('/Database error code "(\d+)"/', $resources->getChildrenErrors()->get(1)->getMessage());

        $this->assertTrue($preEvent);
        $this->assertTrue($postEvent);

        $this->assertCount(0, $domain->getRepository()->findAll());
    }

    public function testCreatesAutoCommitWithErrorValidationAndSuccess()
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar');
        $foo2->setDetail('Detail');

        $objects = array($foo1, $foo2);

        $this->loadFixtures(array());

        $this->assertCount(0, $domain->getRepository()->findAll());
        $resources = $domain->creates($objects, true);
        $this->assertCount(1, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface', $resources->get(0));
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface', $resources->get(1));

        $this->assertSame(ResourceListStatutes::MIXED, $resources->getStatus());
        $this->assertSame(ResourceStatutes::ERROR, $resources->get(0)->getStatus());
        $this->assertSame(ResourceStatutes::CREATED, $resources->get(1)->getStatus());
    }

    public function testCreatesAutoCommit()
    {
        $this->runTestCreates(true);
    }

    public function runTestCreates($autoCommit)
    {
        $domain = $this->createDomain();
        /* @var Foo $foo1 */
        $foo1 = $domain->newInstance();
        $foo1->setName('Bar 1');
        $foo1->setDetail('Detail 1');
        /* @var Foo $foo2 */
        $foo2 = $domain->newInstance();
        $foo2->setName('Bar 2');
        $foo2->setDetail('Detail 2');

        $objects = array($foo1, $foo2);

        $this->loadFixtures(array());

        $this->assertCount(0, $domain->getRepository()->findAll());
        $resources = $domain->creates($objects, $autoCommit);
        $this->assertCount(2, $domain->getRepository()->findAll());

        $this->assertCount(2, $resources);
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface', $resources->get(0));
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface', $resources->get(1));

        $this->assertSame(ResourceListStatutes::SUCCESSFULLY, $resources->getStatus());
        $this->assertSame(ResourceStatutes::CREATED, $resources->get(0)->getStatus());
        $this->assertSame(ResourceStatutes::CREATED, $resources->get(1)->getStatus());
    }
}
