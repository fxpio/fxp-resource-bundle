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
     * Constructor.
     *
     * @param ResourceInterface[] $resources The list of resource
     */
    public function __construct(array $resources)
    {
        $this->addResources($resources);
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
        $this->status = null;
        $this->resources[] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function addAll(ResourceListInterface $otherList)
    {
        $this->addResources($otherList);
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
        $this->status = null;
        $this->resources[$offset] = $resource;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($offset)
    {
        $this->status = null;
        unset($this->resources[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        $errors = array();

        foreach ($this->resources as $i => $resource) {
            if (ResourceStatutes::ERROR === $resource->getStatus()) {
                $errors[$i] = $resource->getErrors();
            }
        }

        return $errors;
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
     * Add resources.
     *
     * @param array|\ArrayAccess $resources
     */
    protected function addResources($resources)
    {
        $this->status = null;

        foreach ($resources as $resource) {
            $this->resources[] = $resource;
        }
    }

    /**
     * Refresh the status of this list.
     */
    protected function refreshStatus()
    {
        $this->status = ResourceListStatutes::SUCCESSFULLY;
        $countErrors = 0;

        foreach ($this->resources as $resource) {
            if (ResourceStatutes::ERROR === $resource->getStatus()) {
                $countErrors++;
            }
        }

        if ($countErrors === $this->count()) {
            $this->status = ResourceListStatutes::ERRORS;
        } elseif ($countErrors > 0) {
            $this->status = ResourceListStatutes::PARTIAL_SUCCESSFULLY;
        }
    }
}
