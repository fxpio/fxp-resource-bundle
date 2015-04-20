<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Domain;

use Doctrine\ORM\EntityManager;
use Sonatra\Bundle\ResourceBundle\Domain\Domain;

/**
 * Tests case for Domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainTest extends \PHPUnit_Framework_TestCase
{
    public function getShortNames()
    {
        return array(
            array(null,              'Foo'),
            array('CustomShortName', 'CustomShortName'),
        );
    }

    /**
     * @dataProvider getShortNames
     *
     * @param string|null $shortName      The short name of domain
     * @param string      $validShortName The valid short name of domain
     */
    public function testShortName($shortName, $validShortName)
    {
        $domain = new Domain('Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo', $shortName);

        $this->assertSame($validShortName, $domain->getShortName());
    }

    public function testCreateQueryBuilder()
    {
        $domain = new Domain('Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo');
        $om = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();
        /* @var EntityManager $om */
        $domain->setObjectManager($om);
        $qb = $domain->createQueryBuilder('f');

        $this->assertInstanceOf('Doctrine\ORM\QueryBuilder', $qb);
    }

    public function testCreateQueryBuilderInvalidObjectManager()
    {
        $msg = 'The "Domain::createQueryBuilder()" method can only be called for a domain with Doctrine ORM Entity Manager';
        $this->setExpectedException('Sonatra\Bundle\ResourceBundle\Exception\BadMethodCallException', $msg);

        $domain = new Domain('Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo');
        $domain->createQueryBuilder();
    }
}
