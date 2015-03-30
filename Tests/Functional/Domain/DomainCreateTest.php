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
use Sonatra\Bundle\ResourceBundle\ResourceStatutes;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo;

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
}
