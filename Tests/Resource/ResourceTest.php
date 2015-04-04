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
    public function testGetFormErrorsWithObjectData()
    {
        $msg = 'The data of resource is not a form instance, used the "getErrors()" method';
        $this->setExpectedException('Sonatra\Bundle\ResourceBundle\Exception\InvalidArgumentException', $msg);

        $resource = new Resource(new Foo());
        $resource->getFormErrors();
    }

    public function testGetFormErrorsWithFormData()
    {
        $fErrors = $this->getMockBuilder('Symfony\Component\Form\FormErrorIterator')
            ->disableOriginalConstructor()
            ->getMock();

        /* @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
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

    public function testUnexpectedTypeException()
    {
        $msg = 'Expected argument of type "object", "integer" given';
        $this->setExpectedException('Sonatra\Bundle\ResourceBundle\Exception\UnexpectedTypeException', $msg);

        /* @var object $object */
        $object = 42;

        new Resource($object);
    }

    public function testUnexpectedTypeExceptionWithForm()
    {
        $msg = 'Expected argument of type "object", "integer" given';
        $this->setExpectedException('Sonatra\Bundle\ResourceBundle\Exception\UnexpectedTypeException', $msg);

        /* @var object $object */
        $object = 42;

        /* @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($object));

        new Resource($form);
    }
}
