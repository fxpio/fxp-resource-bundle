<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Domain;

use Sonatra\Bundle\ResourceBundle\Event\ResourceEvent;
use Sonatra\Bundle\ResourceBundle\Model\SoftDeletableInterface;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceUtil;
use Sonatra\Bundle\ResourceBundle\ResourceEvents;
use Sonatra\Bundle\ResourceBundle\ResourceStatutes;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * A resource domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class Domain extends BaseDomain
{
    /**
     * {@inheritdoc}
     */
    public function deletes(array $resources, $soft = true, $autoCommit = false)
    {
        $list = ResourceUtil::convertObjectsToResourceList(array_values($resources), $this->getClass(), false);

        $this->dispatchEvent(ResourceEvents::PRE_DELETES, new ResourceEvent($this, $list));
        $this->beginTransaction($autoCommit);
        $hasError = $this->doDeleteList($list, $autoCommit, $soft);
        $this->doFlushFinalTransaction($list, $autoCommit, $hasError);

        $this->dispatchEvent(ResourceEvents::POST_DELETES, new ResourceEvent($this, $list));

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    public function undeletes(array $identifiers, $autoCommit = false)
    {
        $list = ResourceUtil::convertObjectsToResourceList($identifiers, $this->getClass());
        //TODO

        return $list;
    }

    /**
     * {@inheritdoc}
     */
    protected function persist(array $resources, $autoCommit = false, $type)
    {
        list($preEvent, $postEvent) = DomainUtil::getEventNames($type);
        $list = ResourceUtil::convertObjectsToResourceList(array_values($resources), $this->getClass());

        $this->dispatchEvent($preEvent, new ResourceEvent($this, $list));
        $this->beginTransaction($autoCommit);
        $hasError = $this->doPersistList($list, $autoCommit, $type);
        $this->doFlushFinalTransaction($list, $autoCommit, $hasError);

        $this->dispatchEvent($postEvent, new ResourceEvent($this, $list));

        return $list;
    }

    /**
     * Do persist the resources.
     *
     * @param ResourceListInterface $resources  The list of object resource instance
     * @param bool                  $autoCommit Commit transaction for each resource or all
     *                                          (continue the action even if there is an error on a resource)
     * @param int                   $type       The type of persist action
     *
     * @return bool Check if there is an error in list
     */
    protected function doPersistList(ResourceListInterface $resources, $autoCommit, $type)
    {
        $hasError = false;
        $hasFlushError = false;

        foreach ($resources as $i => $resource) {
            if (!$autoCommit && $hasError) {
                $resource->setStatus(ResourceStatutes::CANCELED);
                continue;
            } elseif ($autoCommit && $hasFlushError && $hasError) {
                DomainUtil::addResourceError($resource, 'Caused by previous internal database error');
                continue;
            }

            list($successStatus, $hasFlushError) = $this->doPersistResource($resource, $autoCommit, $type);
            $hasError = $this->finalizeResourceStatus($resource, $successStatus, $hasError);
        }

        return $hasError;
    }

    /**
     * Do persist a resource.
     *
     * @param ResourceInterface $resource   The resource
     * @param bool              $autoCommit Commit transaction for each resource or all
     *                                      (continue the action even if there is an error on a resource)
     * @param int               $type       The type of persist action
     *
     * @return array The successStatus and hasFlushError value
     */
    protected function doPersistResource(ResourceInterface $resource, $autoCommit, $type)
    {
        $this->validateResource($resource, $type);
        $object = $resource->getRealData();
        $successStatus = $this->getSuccessStatus($type, $object);
        $hasFlushError = false;

        if ($resource->isValid()) {
            $this->om->persist($object);
            $hasFlushError = $this->doAutoCommitFlushTransaction($resource, $autoCommit);
        }

        return array($successStatus, $hasFlushError);
    }

    /**
     * Do delete the resources.
     *
     * @param ResourceListInterface $resources  The list of object resource instance
     * @param bool                  $autoCommit Commit transaction for each resource or all
     *                                          (continue the action even if there is an error on a resource)
     * @param bool                  $soft       The soft deletable
     *
     * @return bool Check if there is an error in list
     */
    protected function doDeleteList(ResourceListInterface $resources, $autoCommit, $soft = true)
    {
        $hasError = false;
        $hasFlushError = false;

        foreach ($resources as $i => $resource) {
            list($continue, $hasError) = $this->prepareDeleteResource($resource, $autoCommit, $hasError, $hasFlushError);

            if (!$continue) {
                $skipped = $this->doDeleteResource($resource, $soft);
                $hasFlushError = $this->doAutoCommitFlushTransaction($resource, $autoCommit, $skipped);
                $hasError = $this->finalizeResourceStatus($resource, ResourceStatutes::DELETED, $hasError);
            }
        }

        return $hasError;
    }

    /**
     * Prepare the deletion of resource.
     *
     * @param ResourceInterface $resource      The resource
     * @param bool              $autoCommit    Commit transaction for each resource or all
     *                                         (continue the action even if there is an error on a resource)
     * @param bool              $hasError      Check if there is an previous error
     * @param bool              $hasFlushError Check if there is an previous flush error
     *
     * @return array The check if the delete action must be continued and check if there is an error
     */
    protected function prepareDeleteResource(ResourceInterface $resource, $autoCommit, $hasError, $hasFlushError)
    {
        $continue = false;

        if (!$autoCommit && $hasError) {
            $resource->setStatus(ResourceStatutes::CANCELED);
            $continue = true;
        } elseif ($autoCommit && $hasFlushError && $hasError) {
            DomainUtil::addResourceError($resource, 'Caused by previous internal database error');
            $continue = true;
        } elseif (null !== $idError = $this->getErrorIdentifier($resource->getRealData(), static::TYPE_DELETE)) {
            $hasError = true;
            $resource->setStatus(ResourceStatutes::ERROR);
            $resource->getErrors()->add(new ConstraintViolation($idError, $idError, array(), null, null, null));
            $continue = true;
        }

        return array($continue, $hasError);
    }

    /**
     * Do delete a resource.
     *
     * @param ResourceInterface $resource The resource
     * @param bool              $soft     The soft deletable
     *
     * @return bool Check if the resource is skipped or deleted
     */
    protected function doDeleteResource(ResourceInterface $resource, $soft)
    {
        $skipped = false;
        $object = $resource->getRealData();

        if ($object instanceof SoftDeletableInterface) {
            if ($soft) {
                if ($object->isDeleted()) {
                    $skipped = true;
                } else {
                    $this->om->remove($object);
                }
            } else {
                if (!$object->isDeleted()) {
                    $object->setDeletedAt(new \DateTime());
                }
                $this->om->remove($object);
            }
        } else {
            $this->om->remove($object);
        }

        return $skipped;
    }
}
