<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Converter;

use Sonatra\Bundle\ResourceBundle\Converter\ConverterRegistry;
use Sonatra\Bundle\ResourceBundle\Converter\ConverterRegistryInterface;

/**
 * Tests case for converter registry.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ConverterRegistryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConverterRegistryInterface
     */
    protected $registry;

    protected function setUp()
    {
        $converter = $this->getMockBuilder('Sonatra\Bundle\ResourceBundle\Converter\ConverterInterface')->getMock();
        $converter->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('foo'));

        $this->registry = new ConverterRegistry(array(
            $converter,
        ));
    }

    /**
     * @expectedException \Sonatra\Bundle\ResourceBundle\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "Sonatra\Bundle\ResourceBundle\Converter\ConverterInterface", "DateTime" given
     */
    public function testUnexpectedTypeException()
    {
        new ConverterRegistry(array(
            new \DateTime(),
        ));
    }

    public function testHas()
    {
        $this->assertTrue($this->registry->has('foo'));
        $this->assertFalse($this->registry->has('bar'));
    }

    /**
     * @expectedException \Sonatra\Bundle\ResourceBundle\Exception\UnexpectedTypeException
     * @expectedExceptionMessageRegExp /Expected argument of type "(\w+)", "(\w+)" given/
     */
    public function testGetInvalidType()
    {
        $this->registry->get(42);
    }

    /**
     * @expectedException \Sonatra\Bundle\ResourceBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp /Could not load content converter "(\w+)"/
     */
    public function testGetNonExistentConverter()
    {
        $this->registry->get('bar');
    }

    public function testGet()
    {
        $converter = $this->registry->get('foo');

        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Converter\ConverterInterface', $converter);
    }
}
