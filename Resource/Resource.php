<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Resource;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Action resource for domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class Resource implements ResourceInterface
{
    /**
     * @var string
     */
    protected $status;

    /**
     * @var object
     */
    protected $data;

    /**
     * @var ConstraintViolationListInterface
     */
    protected $errors;

    /**
     * Constructor.
     *
     * @param string                           $status The action status of resource
     * @param object                           $data   The data instance of resource
     * @param ConstraintViolationListInterface $errors The list of errors
     */
    public function __construct($status, $data, ConstraintViolationListInterface $errors)
    {
        $this->status = $status;
        $this->data = $data;
        $this->errors = $errors;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
