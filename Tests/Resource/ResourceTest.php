<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Resource;

use Sonatra\Bundle\ResourceBundle\Resource\Resource;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo;
use Symfony\Component\Form\Test\FormInterface;

/**
 * Tests case for resource.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \Sonatra\Bundle\ResourceBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage The data of resource is not a form instance, used the "getErrors()" method
     */
    public function testGetFormErrorsWithObjectData()
    {
        $resource = new Resource(new Foo());
        $resource->getFormErrors();
    }

    public function testGetFormErrorsWithFormData()
    {
        $fErrors = $this->getMockBuilder('Symfony\Component\Form\FormErrorIterator')
            ->disableOriginalConstructor()
            ->getMock();

        /* @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')->getMock();
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue(new Foo()));
        $form->expects($this->any())
            ->method('getErrors')
            ->will($this->returnValue($fErrors));

        $resource = new Resource($form);
        $errors = $resource->getFormErrors();

        $this->assertInstanceOf('Symfony\Component\Form\FormErrorIterator', $errors);
    }

    /**
     * @expectedException \Sonatra\Bundle\ResourceBundle\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "object", "integer" given
     */
    public function testUnexpectedTypeException()
    {
        /* @var object $object */
        $object = 42;

        new Resource($object);
    }

    /**
     * @expectedException \Sonatra\Bundle\ResourceBundle\Exception\UnexpectedTypeException
     * @expectedExceptionMessage Expected argument of type "object", "integer" given
     */
    public function testUnexpectedTypeExceptionWithForm()
    {
        /* @var object $object */
        $object = 42;

        /* @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')->getMock();
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($object));

        new Resource($form);
    }
}
