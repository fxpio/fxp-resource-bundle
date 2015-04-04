<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Handler;

use Sonatra\Bundle\ResourceBundle\Converter\ConverterRegistryInterface;
use Sonatra\Bundle\ResourceBundle\Exception\InvalidArgumentException;
use Sonatra\Bundle\ResourceBundle\Exception\InvalidResourceException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * A form handler.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class FormHandler implements FormHandlerInterface
{
    /**
     * @var ConverterRegistryInterface
     */
    protected $converterRegistry;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Constructor.
     *
     * @param ConverterRegistryInterface $converterRegistry The converter registry
     * @param FormFactoryInterface       $formFactory       The form factory
     * @param RequestStack               $requestStack      The request stack
     *
     * @throws InvalidArgumentException When the current request is request stack is empty
     */
    public function __construct(ConverterRegistryInterface $converterRegistry,
                                FormFactoryInterface $formFactory, RequestStack $requestStack)
    {
        $this->converterRegistry = $converterRegistry;
        $this->formFactory = $formFactory;
        $this->request = $requestStack->getCurrentRequest();

        if (null === $this->request) {
            throw new InvalidArgumentException('The current request is required in request stack');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function processForm(FormConfigInterface $config, $object)
    {
        return current($this->process($config, array($object), false));
    }

    /**
     * {@inheritdoc}
     */
    public function processForms(FormConfigInterface $config, array $objects)
    {
        return $this->process($config, $objects, true);
    }

    /**
     * Create the list of form for the object instances.
     *
     * @param FormConfigInterface $config  The form config
     * @param object[]            $objects The list of object instance
     * @param bool                $isList  Check if the request data is a list
     *
     * @return FormInterface[]
     *
     * @throws InvalidResourceException When the size if request data and the object instances is different
     */
    private function process(FormConfigInterface $config, array $objects, $isList)
    {
        $forms = array();
        $converter = $this->converterRegistry->get($config->getConverter());
        $dataList = $converter->convert($this->request->getContent());

        if (!$isList) {
            $dataList = array($dataList);
        }

        $objects = array_values($objects);
        $dataList = array_values($dataList);

        if (count($objects) !== count($dataList)) {
            $msg = 'The size of the request data list (%s) is different that the object instance list (%s)';
            throw new InvalidResourceException(sprintf($msg, count($dataList), count($objects)));
        }

        foreach ($objects as $i => $object) {
            $form = $this->formFactory->create($config->getType(), $object, $config->getOptions());
            $form->submit($dataList[$i], $config->getSubmitClearMissing());
            $forms[] = $form;
        }

        return $forms;
    }
}
