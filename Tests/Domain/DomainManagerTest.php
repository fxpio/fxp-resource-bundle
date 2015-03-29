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

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Sonatra\Bundle\DefaultValueBundle\DefaultValue\ObjectFactoryInterface;
use Sonatra\Bundle\ResourceBundle\Domain\DomainInterface;
use Sonatra\Bundle\ResourceBundle\Domain\DomainManager;
use Sonatra\Bundle\ResourceBundle\Domain\DomainManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Tests case for Domain Manager.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DomainManagerInterface
     */
    protected $manager;

    protected function setUp()
    {
        /* @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $metaBar */
        $metaBar = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metaBar->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Bar'));

        /* @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $metaFoo */
        $metaFoo = $this->getMock('Doctrine\Common\Persistence\Mapping\ClassMetadata');
        $metaFoo->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('Foo'));

        /* @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $om */
        $om = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $om->expects($this->any())
            ->method('getClassMetadata')
            ->will($this->returnCallback(function ($value) use ($metaBar, $metaFoo) {
                $ret = null;
                if ('Bar' === $value) {
                    $ret = $metaBar;
                }
                if ('Foo' === $value) {
                    $ret = $metaFoo;
                }

                return $ret;
            }));

        /* @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $or */
        $or = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $or->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnCallback(function ($value) use ($om) {
                if ('InvalidClass' === $value) {
                    return;
                }

                return $om;
            }));

        /* @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $ed */
        $ed = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        /* @var ObjectFactoryInterface|\PHPUnit_Framework_MockObject_MockObject $of */
        $of = $this->getMock('Sonatra\Bundle\DefaultValueBundle\DefaultValue\ObjectFactoryInterface');

        /* @var ValidatorInterface|\PHPUnit_Framework_MockObject_MockObject $val */
        $val = $this->getMock('Symfony\Component\Validator\Validator\ValidatorInterface');

        /* @var DomainInterface|\PHPUnit_Framework_MockObject_MockObject $domain */
        $domain = $this->getMock('Sonatra\Bundle\ResourceBundle\Domain\DomainInterface');
        $domain->expects($this->any())
            ->method('getClass')
            ->will($this->returnValue('Foo'));

        $this->manager = new DomainManager(array($domain), $or, $ed, $of, $val);
    }

    public function testConstructor()
    {
        $this->assertCount(1, $this->manager->all());
    }

    public function testHasDomainClass()
    {
        $this->assertTrue($this->manager->has('Foo'));
        $this->assertFalse($this->manager->has('Bar'));
    }

    public function testAdd()
    {
        $this->assertCount(1, $this->manager->all());

        /* @var DomainInterface|\PHPUnit_Framework_MockObject_MockObject $domain */
        $domain = $this->getMock('Sonatra\Bundle\ResourceBundle\Domain\DomainInterface');
        $domain->expects($this->any())
            ->method('getClass')
            ->will($this->returnValue('Bar'));

        $this->manager->add($domain);

        $this->assertCount(2, $this->manager->all());

        $this->assertTrue($this->manager->has('Foo'));
        $this->assertTrue($this->manager->has('Bar'));
    }

    public function testRemove()
    {
        $this->assertCount(1, $this->manager->all());

        $this->manager->remove('Foo');

        $this->assertCount(0, $this->manager->all());
    }

    public function testGetNonRegisteredClass()
    {
        $msg = '/The "(\w+)" class is not registered in doctrine/';
        $this->setExpectedExceptionRegExp('Sonatra\Bundle\ResourceBundle\Exception\InvalidArgumentException', $msg);

        $this->manager->get('InvalidClass');
    }

    public function testGetDomainNotManaged()
    {
        $msg = '/The resource domain for "(\w+)" class is not managed/';
        $this->setExpectedExceptionRegExp('Sonatra\Bundle\ResourceBundle\Exception\InvalidArgumentException', $msg);
        $this->manager->get('Bar');
    }

    public function testGetDomainWithCache()
    {
        $dom1 = $this->manager->get('Foo');
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Domain\DomainInterface', $dom1);

        $dom2 = $this->manager->get('Foo');
        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Domain\DomainInterface', $dom2);

        $this->assertSame($dom1, $dom2);
    }
}
