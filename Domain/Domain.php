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

use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DriverException;
use Doctrine\ORM\EntityManagerInterface;
use Sonatra\Bundle\DefaultValueBundle\DefaultValue\ObjectFactoryInterface;
use Sonatra\Bundle\ResourceBundle\Event\ResourceEvent;
use Sonatra\Bundle\ResourceBundle\Model\SoftDeletableInterface;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceList;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceListInterface;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceUtil;
use Sonatra\Bundle\ResourceBundle\ResourceEvents;
use Sonatra\Bundle\ResourceBundle\Exception\InvalidConfigurationException;
use Sonatra\Bundle\ResourceBundle\ResourceStatutes;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * A resource domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class Domain implements DomainInterface
{
    const TYPE_CREATE = 0;

    const TYPE_UPDATE = 1;

    const TYPE_UPSERT = 2;

    const TYPE_DELETE = 3;

    /**
     * @var string
     */
    protected $class;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var ObjectManager
     */
    protected $om;

    /**
     * @var EventDispatcherInterface
     */
    protected $ed;

    /**
     * @var ObjectFactoryInterface
     */
    protected $of;

    /**
     * @var ValidatorInterface;
     */
    protected $validator;

    /**
     * @var string
     */
    protected $eventPrefix;

    /**
     * @var PropertyAccessor
     */
    protected $propertyAccess;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * Constructor.
     *
     * @param string $class The class name
     */
    public function __construct($class)
    {
        $this->class = $class;
        $this->eventPrefix = $this->formatEventPrefix($class);
        $this->propertyAccess = PropertyAccess::createPropertyAccessor();
        $this->debug = false;
    }

    /**
     * {@inheritdoc}
     */
    public function setDebug($debug)
    {
        $this->debug = (bool) $debug;
    }

    /**
     * {@inheritdoc}
     */
    public function setObjectManager(ObjectManager $om)
    {
        $this->om = $om;

        try {
            $this->getClassMetadata();
        } catch (MappingException $e) {
            $msg = sprintf('The "%s" class is not managed by doctrine object manager', $this->getClass());
            throw new InvalidConfigurationException($msg, 0, $e);
        }

        if ($om instanceof EntityManagerInterface) {
            $this->connection = $om->getConnection();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setEventDispatcher(EventDispatcherInterface $ed)
    {
        $this->ed = $ed;
    }

    /**
     * {@inheritdoc}
     */
    public function setObjectFactory(ObjectFactoryInterface $of)
    {
        $this->of = $of;
    }

    /**
     * {@inheritdoc}
     */
    public function setValidator(ValidatorInterface $validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function getRepository()
    {
        return $this->om->getRepository($this->getClass());
    }

    /**
     * {@inheritdoc}
     */
    public function getClassMetadata()
    {
        return $this->om->getClassMetadata($this->getClass());
    }

    /**
     * {@inheritdoc}
     */
    public function getEventPrefix()
    {
        return $this->eventPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function newInstance(array $options = array())
    {
        return $this->of->create($this->getClass(), null, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function create($resource)
    {
        return $this->creates(array($resource), true)->getIterator()->current();
    }

    /**
     * {@inheritdoc}
     */
    public function creates(array $resources, $autoCommit = false)
    {
        return $this->persist($resources, $autoCommit, Domain::TYPE_CREATE);
    }

    /**
     * {@inheritdoc}
     */
    public function update($resource)
    {
        return $this->updates(array($resource), true)->getIterator()->current();
    }

    /**
     * {@inheritdoc}
     */
    public function updates(array $resources, $autoCommit = false)
    {
        return $this->persist($resources, $autoCommit, Domain::TYPE_UPDATE);
    }

    /**
     * {@inheritdoc}
     */
    public function upsert($resource)
    {
        return $this->upserts(array($resource), true)->getIterator()->current();
    }

    /**
     * {@inheritdoc}
     */
    public function upserts(array $resources, $autoCommit = false)
    {
        return $this->persist($resources, $autoCommit, Domain::TYPE_UPSERT);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($resource, $soft = true)
    {
        return $this->deletes(array($resource), true)->getIterator()->current();
    }

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
    public function undelete($identifier)
    {
        return $this->undeletes(array($identifier), true)->getIterator()->current();
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
     * Persist the resources.
     *
     * Warning: It's recommended to limit the number of resources.
     *
     * @param object[]|FormInterface[] $resources  The list of object resource instance
     * @param bool                     $autoCommit Commit transaction for each resource or all
     *                                             (continue the action even if there is an error on a resource)
     * @param int                      $type       The type of persist action
     *
     * @return ResourceList
     */
    protected function persist(array $resources, $autoCommit = false, $type)
    {
        list($preEvent, $postEvent) = $this->getEventNames($type);
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
                $this->addResourceError($resource, 'Caused by previous internal database error');
                continue;
            }

            list($successStatus, $hasFlushError) = $this->doPersist($resource, $autoCommit, $type);
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
    protected function doPersist(ResourceInterface $resource, $autoCommit, $type)
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
     * Do the flush transaction for auto commit.
     *
     * @param ResourceInterface $resource   The resource
     * @param bool              $autoCommit The auto commit
     * @param bool              $skipped    Check if the resource is skipped
     *
     * @return bool Returns if there is an flush error
     */
    protected function doAutoCommitFlushTransaction(ResourceInterface $resource, $autoCommit, $skipped = false)
    {
        $hasFlushError = false;

        if ($autoCommit && !$skipped) {
            $rErrors = $this->flushTransaction($resource->getRealData());
            $resource->getErrors()->addAll($rErrors);
            $hasFlushError = $rErrors->count() > 0;
        }

        return $hasFlushError;
    }

    /**
     * Do flush the final transaction for non auto commit.
     *
     * @param ResourceListInterface $resources  The list of object resource instance
     * @param bool                  $autoCommit Commit transaction for each resource or all
     *                                          (continue the action even if there is an error on a resource)
     * @param bool                  $hasError   Check if there is an error
     *
     * @return bool Check if there is an error in list
     */
    protected function doFlushFinalTransaction(ResourceListInterface $resources, $autoCommit, $hasError)
    {
        if (!$autoCommit) {
            if ($hasError) {
                $this->cancelTransaction();
            } else {
                $errors = $this->flushTransaction();

                if (count($errors) > 0) {
                    $resources->getErrors()->addAll($errors);
                    foreach ($resources as $resource) {
                        $resource->setStatus(ResourceStatutes::ERROR);
                    }
                }
            }
        }
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
            if (!$autoCommit && $hasError) {
                $resource->setStatus(ResourceStatutes::CANCELED);
                continue;
            } elseif ($autoCommit && $hasFlushError && $hasError) {
                $this->addResourceError($resource, 'Caused by previous internal database error');
                continue;
            } elseif (null !== $idError = $this->getErrorIdentifier($resource->getRealData(), static::TYPE_DELETE)) {
                $hasError = true;
                $resource->setStatus(ResourceStatutes::ERROR);
                $resource->getErrors()->add(new ConstraintViolation($idError, $idError, array(), null, null, null));
                continue;
            }

            $skipped = $this->doDelete($resource, $soft);
            $hasFlushError = $this->doAutoCommitFlushTransaction($resource, $autoCommit, $skipped);
            $hasError = $this->finalizeResourceStatus($resource, ResourceStatutes::DELETED, $hasError);
        }

        return $hasError;
    }

    /**
     * Do delete a resource.
     *
     * @param ResourceInterface $resource The resource
     * @param bool              $soft     The soft deletable
     *
     * @return bool Check if the resource is skipped or deleted
     */
    protected function doDelete(ResourceInterface $resource, $soft)
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

    /**
     * Add the error in resource.
     *
     * @param ResourceInterface $resource The resource
     * @param string            $message  The error message
     */
    public function addResourceError(ResourceInterface $resource, $message)
    {
        $resource->setStatus(ResourceStatutes::ERROR);
        $resource->getErrors()->add(new ConstraintViolation($message, $message, array(), null, null, null));
    }

    /**
     * Finalize the action for a resource.
     *
     * @param ResourceInterface $resource
     * @param $status
     * @param $hasError
     *
     * @return bool Returns the new hasError value
     */
    protected function finalizeResourceStatus(ResourceInterface $resource, $status, $hasError)
    {
        if ($resource->isValid()) {
            $resource->setStatus($status);
        } else {
            $hasError = true;
            $resource->setStatus(ResourceStatutes::ERROR);
            $this->om->detach($resource->getRealData());
        }

        return $hasError;
    }

    /**
     * Get the event names of persist action.
     *
     * @param int $type The type of persist
     *
     * @return array The list of pre event name and post event name
     */
    protected function getEventNames($type)
    {
        $names = array(ResourceEvents::PRE_UPSERTS, ResourceEvents::POST_UPSERTS);

        if (Domain::TYPE_CREATE === $type) {
            $names = array(ResourceEvents::PRE_CREATES, ResourceEvents::POST_CREATES);
        } elseif (Domain::TYPE_UPDATE === $type) {
            $names = array(ResourceEvents::PRE_UPDATES, ResourceEvents::POST_UPDATES);
        }

        return $names;
    }

    /**
     * Dispatch the event.
     *
     * @param string        $name  The event name
     * @param ResourceEvent $event The event
     *
     * @return ResourceEvent
     */
    protected function dispatchEvent($name, ResourceEvent $event)
    {
        $name = $this->eventPrefix.$name;

        return $this->ed->dispatch($name, $event);
    }

    /**
     * Begin automatically the database transaction.
     *
     * @param bool $autoCommit Check if each resource must be flushed immediately or in the end
     */
    protected function beginTransaction($autoCommit = false)
    {
        if (!$autoCommit && null !== $this->connection) {
            $this->connection->beginTransaction();
        }
    }

    /**
     * Flush data in database with automatic declaration of the transaction for the collection.
     *
     * @param object|null $object The resource for auto commit or null for flush at the end
     *
     * @return ConstraintViolationList
     */
    protected function flushTransaction($object = null)
    {
        $violations = new ConstraintViolationList();

        try {
            $this->flush($object);

            if (null !== $this->connection && null === $object) {
                $this->connection->commit();
            }
        } catch (\Exception $e) {
            $this->flushTransactionException($e, $violations, $object);
        }

        return $violations;
    }

    /**
     * Do the action when there is an exception on flush transaction.
     *
     * @param \Exception                       $e          The exception on flush transaction
     * @param ConstraintViolationListInterface $violations The constraint violation list
     * @param object|null                      $object     The resource for auto commit or null for flush at the end
     */
    protected function flushTransactionException(\Exception $e, ConstraintViolationListInterface $violations, $object = null)
    {
        if (null !== $this->connection && null === $object) {
            $this->connection->rollback();
        }

        $message = $e instanceof DriverException
            ? DomainUtil::extractDriverExceptionMessage($e, $this->debug)
            : $e->getMessage();

        $violations->add(new ConstraintViolation($message, $message, array(), null, null, null));
    }

    /**
     * Cancel transaction.
     */
    protected function cancelTransaction()
    {
        if (null !== $this->connection) {
            $this->connection->rollBack();
        }
    }

    /**
     * Flush the object data in database.
     *
     * @param object|null $object The resource data for auto commit or null for flush at the end
     */
    protected function flush($object = null)
    {
        $this->om->flush();

        if (null !== $object) {
            $this->om->detach($object);
        } else {
            $this->om->clear();
        }
    }

    /**
     * Validate the resource and get the error list.
     *
     * @param ResourceInterface $resource The resource
     * @param int               $type     The type of persist
     */
    protected function validateResource($resource, $type)
    {
        $idError = $this->getErrorIdentifier($resource->getRealData(), $type);
        $data = $resource->getData();

        if ($data instanceof FormInterface) {
            if (!$data->isSubmitted()) {
                $data->submit(array());
            }
        } else {
            $errors = $this->validator->validate($data);
            $resource->getErrors()->addAll($errors);
        }

        if (null !== $idError) {
            $resource->getErrors()->add(new ConstraintViolation($idError, $idError, array(), '', '', ''));
        }
    }

    /**
     * Get the error of identifier.
     *
     * @param object $object The object data
     * @param int    $type   The type of persist
     *
     * @return string|null
     */
    protected function getErrorIdentifier($object, $type)
    {
        $idValue = $this->getIdentifier($object);
        $idError = null;

        if (Domain::TYPE_CREATE === $type && null !== $idValue) {
            $idError = 'The resource cannot be created because it has an identifier';
        } elseif (Domain::TYPE_UPDATE === $type && null === $idValue) {
            $idError = 'The resource cannot be updated because it has not an identifier';
        } elseif (Domain::TYPE_DELETE === $type && null === $idValue) {
            $idError = 'The resource cannot be deleted because it has not an identifier';
        }

        return $idError;
    }

    /**
     * Get the success status.
     *
     * @param int    $type   The type of persist
     * @param object $object The resource instance
     *
     * @return string
     */
    protected function getSuccessStatus($type, $object)
    {
        if (Domain::TYPE_CREATE === $type) {
            return ResourceStatutes::CREATED;
        }
        if (Domain::TYPE_UPDATE === $type) {
            return ResourceStatutes::UPDATED;
        }

        return null === $this->getIdentifier($object)
            ? ResourceStatutes::CREATED
            : ResourceStatutes::UPDATED;
    }

    /**
     * Get the value of resource identifier.
     *
     * @param object $object The resource object
     *
     * @return int|string|null
     */
    protected function getIdentifier($object)
    {
        $meta = $this->om->getClassMetadata(get_class($object));
        $ids = $meta->getIdentifier();
        $value = null;

        foreach ($ids as $id) {
            $idVal = $this->propertyAccess->getValue($object, $id);

            if (null !== $idVal) {
                $value = $idVal;
                break;
            }
        }

        return $value;
    }

    /**
     * Format the prefix event.
     *
     * @param string $class The class name
     *
     * @return string
     */
    private function formatEventPrefix($class)
    {
        $name = Container::underscore($class);

        return str_replace(array('\\', '/'), '_', $name);
    }
}
