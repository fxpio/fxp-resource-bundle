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

use Liip\FunctionalTestBundle\Test\WebTestCase;
use Sonatra\Bundle\ResourceBundle\Converter\ConverterRegistryInterface;
use Sonatra\Bundle\ResourceBundle\Handler\FormHandler;
use Sonatra\Bundle\ResourceBundle\Handler\FormHandlerInterface;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\TestAppKernel;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Abstract class for Functional tests for Form Handler.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
abstract class AbstractFormHandlerTest extends WebTestCase
{
    protected static function createKernel(array $options = array())
    {
        return new TestAppKernel('test', true);
    }

    /**
     * Create form handler.
     *
     * @param Request|null $request The request for request stack
     * @param int|null     $limit   The limit
     *
     * @return FormHandlerInterface
     */
    protected function createFormHandler(Request $request = null, $limit = null)
    {
        $container = $this->getContainer();

        /* @var ConverterRegistryInterface $converterRegistry */
        $converterRegistry = $container->get('sonatra_resource.converter_registry');
        /* @var FormFactoryInterface $formFactory */
        $formFactory = $container->get('form.factory');
        /* @var RequestStack $requestStack */
        $requestStack = $container->get('request_stack');

        if (null !== $request) {
            $requestStack->push($request);
        }

        return new FormHandler($converterRegistry, $formFactory, $requestStack, $limit);
    }
}
