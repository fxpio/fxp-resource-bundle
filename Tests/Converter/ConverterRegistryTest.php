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
        $converter = $this->getMock('Sonatra\Bundle\ResourceBundle\Converter\ConverterInterface');
        $converter->expects($this->any())
            ->method('getName')
            ->will($this->returnValue('foo'));

        $this->registry = new ConverterRegistry(array(
            $converter,
        ));
    }

    public function testUnexpectedTypeException()
    {
        $msg = 'Expected argument of type "Sonatra\Bundle\ResourceBundle\Converter\ConverterInterface", "DateTime" given';
        $this->setExpectedException('Sonatra\Bundle\ResourceBundle\Exception\UnexpectedTypeException', $msg);

        new ConverterRegistry(array(
            new \DateTime(),
        ));
    }

    public function testHas()
    {
        $this->assertTrue($this->registry->has('foo'));
        $this->assertFalse($this->registry->has('bar'));
    }

    public function testGetInvalidType()
    {
        $msg = '/Expected argument of type "(\w+)", "(\w+)" given/';
        $this->setExpectedExceptionRegExp('Sonatra\Bundle\ResourceBundle\Exception\UnexpectedTypeException', $msg);

        $this->registry->get(42);
    }

    public function testGetNonExistentConverter()
    {
        $msg = '/Could not load content converter "(\w+)"/';
        $this->setExpectedExceptionRegExp('Sonatra\Bundle\ResourceBundle\Exception\InvalidArgumentException', $msg);

        $this->registry->get('bar');
    }

    public function testGet()
    {
        $converter = $this->registry->get('foo');

        $this->assertInstanceOf('Sonatra\Bundle\ResourceBundle\Converter\ConverterInterface', $converter);
    }
}
