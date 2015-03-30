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

/**
 * Functional tests for Domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainTest extends AbstractDomainTest
{
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
}
