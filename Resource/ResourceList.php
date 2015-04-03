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

use Sonatra\Bundle\ResourceBundle\ResourceStatutes;
use Sonatra\Bundle\ResourceBundle\ResourceListStatutes;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Resource list.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ResourceList implements \IteratorAggregate, ResourceListInterface
{
    /**
     * @var string
     */
    protected $status;

    /**
     * @var ResourceInterface[]
     */
    protected $resources;

    /**
     * @var ConstraintViolationListInterface
     */
    protected $errors;

    /**
     * @var ConstraintViolationListInterface|FormErrorIterator[]|null
     */
    protected $childrenErrors;

    /**
     * Constructor.
     *
     * @param ResourceInterface[]              $resources The list of resource
     * @param ConstraintViolationListInterface $errors    The list of errors
     */
    public function __construct(array $resources = array(), ConstraintViolationListInterface $errors = null)
    {
        $this->resources = array();
        $this->errors = null !== $errors ? $errors : new ConstraintViolationList();

        foreach ($resources as $resource) {
            $this->add($resource);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStatus()
    {
        if (null === $this->status) {
            $this->refreshStatus();
        }

        return $this->status;
    }

    /**
     * {@inheritdoc}
     */
    public function getResources()
    {
        return $this->resources;
    }

    /**
     * {@inheritdoc}
     */
    public function add(ResourceInterface $resource)
    {
        $this->reset();
        $this->resources[] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function addAll(ResourceListInterface $otherList)
    {
        $this->reset();

        foreach ($otherList as $resource) {
            $this->resources[] = $resource;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function get($offset)
    {
        if (!isset($this->resources[$offset])) {
            throw new \OutOfBoundsException(sprintf('The offset "%s" does not exist.', $offset));
        }

        return $this->resources[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function has($offset)
    {
        return isset($this->resources[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function set($offset, ResourceInterface $resource)
    {
        $this->reset();
        $this->resources[$offset] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($offset)
    {
        $this->reset();
        unset($this->resources[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * {@inheritdoc}
     */
    public function hasErrors()
    {
        if ($this->getErrors()->count() > 0) {
            return true;
        }

        foreach ($this->resources as $i => $resource) {
            if (ResourceStatutes::ERROR === $resource->getStatus() && !$resource->isValid()) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        return count($this->resources);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $resource)
    {
        if (null === $offset) {
            $this->add($resource);
        } else {
            $this->set($offset, $resource);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    /**
     * Reset the value of status and children errors.
     */
    protected function reset()
    {
        $this->status = null;
        $this->childrenErrors = null;
    }

    /**
     * Refresh the status of this list.
     */
    protected function refreshStatus()
    {
        $countPending = 0;
        $countCancel = 0;
        $countError = 0;
        $countSuccess = 0;

        foreach ($this->resources as $resource) {
            switch ($resource->getStatus()) {
                case ResourceStatutes::PENDING:
                    $countPending++;
                    break;
                case ResourceStatutes::CANCELED:
                    $countCancel++;
                    break;
                case ResourceStatutes::ERROR:
                    $countError++;
                    break;
                default:
                    $countSuccess++;
                    break;
            }
        }

        $this->status = $this->getStatusValue($countPending, $countCancel, $countError, $countSuccess);
    }

    /**
     * Get the final status value.
     *
     * @param int $countPending
     * @param int $countCancel
     * @param int $countError
     * @param int $countSuccess
     *
     * @return string
     */
    private function getStatusValue($countPending, $countCancel, $countError, $countSuccess)
    {
        $status = ResourceListStatutes::SUCCESSFULLY;
        $count = $this->count();

        if ($count > 0) {
            $status = ResourceListStatutes::MIXED;

            if ($count === $countPending) {
                $status = ResourceListStatutes::PENDING;
            } elseif ($count === $countCancel) {
                $status = ResourceListStatutes::CANCEL;
            } elseif ($count === $countError) {
                $status = ResourceListStatutes::ERROR;
            } elseif ($count === $countSuccess) {
                $status = ResourceListStatutes::SUCCESSFULLY;
            }
        }

        return $status;
    }
}
