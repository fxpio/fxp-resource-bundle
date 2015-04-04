<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Functional\Handler;

use Sonatra\Bundle\ResourceBundle\Handler\FormConfig;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Form\FooType;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class for Functional tests for Form Handler.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class FormHandlerTest extends AbstractFormHandlerTest
{
    public function testEmptyCurrentRequestException()
    {
        $msg = 'The current request is required in request stack';
        $this->setExpectedException('Sonatra\Bundle\ResourceBundle\Exception\InvalidArgumentException', $msg);

        $this->createFormHandler();
    }

    public function testProcessForm()
    {
        $data = array(
            'name' => 'Bar',
            'detail' => 'Detail',
        );
        $request = Request::create('test', Request::METHOD_POST, array(), array(), array(), array(), json_encode($data));
        $handler = $this->createFormHandler($request);

        $object = new Foo();
        $config = new FormConfig(new FooType());

        $form = $handler->processForm($config, $object);

        $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form);
        $this->assertInstanceOf(get_class($object), $form->getData());
        $this->assertSame($object, $form->getData());
        $this->assertTrue($form->isSubmitted());
    }

    public function testProcessForms()
    {
        $data = array(
            array(
                'name' => 'Bar 1',
                'detail' => 'Detail 1',
            ),
            array(
                'name' => 'Bar 2',
                'detail' => 'Detail 2',
            ),
            array(
                'name' => 'Bar 3',
                'detail' => 'Detail 3',
            ),
        );
        $request = Request::create('test', Request::METHOD_POST, array(), array(), array(), array(), json_encode($data));
        $handler = $this->createFormHandler($request);

        $objects = array(
            new Foo(),
            new Foo(),
            new Foo(),
        );
        $config = new FormConfig(new FooType());

        $forms = $handler->processForms($config, $objects);

        $this->assertSame(count($data), count($forms));
        $this->assertTrue(count($forms) > 0);

        foreach ($forms as $i => $form) {
            $this->assertInstanceOf('Symfony\Component\Form\FormInterface', $form);
            $this->assertInstanceOf(get_class($objects[$i]), $form->getData());
            $this->assertSame($objects[$i], $form->getData());
            $this->assertTrue($form->isSubmitted());
        }
    }

    public function testProcessFormsWithDifferentSize()
    {
        $msg = 'The size of the request data list (1) is different that the object instance list (2)';
        $this->setExpectedException('Sonatra\Bundle\ResourceBundle\Exception\InvalidResourceException', $msg);

        $data = array(
            array(
                'name' => 'Bar 1',
                'detail' => 'Detail 1',
            ),
        );
        $request = Request::create('test', Request::METHOD_POST, array(), array(), array(), array(), json_encode($data));
        $handler = $this->createFormHandler($request);

        $objects = array(
            new Foo(),
            new Foo(),
        );
        $config = new FormConfig(new FooType());

        $handler->processForms($config, $objects);
    }
}
