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
use Sonatra\Bundle\ResourceBundle\Resource\ResourceUtil;
use Sonatra\Bundle\ResourceBundle\ResourceEvents;
use Sonatra\Bundle\ResourceBundle\Exception\InvalidConfigurationException;
use Sonatra\Bundle\ResourceBundle\ResourceStatutes;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * A resource domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class Domain implements DomainInterface
{
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
     * Constructor.
     *
     * @param string $class The class name
     */
    public function __construct($class)
    {
        $this->class = $class;
        $this->eventPrefix = $this->formatEventPrefix($class);
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
        $resources = array_values($resources);
        $hasError = false;
        $list = ResourceUtil::convertObjectsToResourceList($resources, $this->getClass());

        $this->dispatchEvent(ResourceEvents::PRE_CREATES, new ResourceEvent($this, $list));
        $this->beginTransaction($autoCommit);

        foreach ($resources as $i => $resource) {
            $listResources = $list->getResources();

            if (!$autoCommit && $hasError) {
                $listResources[$i]->setStatus(ResourceStatutes::CANCELED);
                continue;
            }

            $errors = $this->validator->validate($resource);

            if (0 === count($errors)) {
                $this->om->persist($resource);

                if ($autoCommit) {
                    $errors = $this->flushTransaction($resource);
                }
            }

            if (0 !== count($errors)) {
                $hasError = true;
                $listResources[$i]->setStatus(ResourceStatutes::ERROR);
                $listResources[$i]->getErrors()->addAll($errors);
            } else {
                $listResources[$i]->setStatus(ResourceStatutes::CREATED);
            }
        }

        if (!$autoCommit) {
            $errors = $this->flushTransaction();

            if (count($errors) > 0) {
                $list->getErrors()->addAll($errors);
                foreach ($list->getResources() as $resource) {
                    $resource->setStatus(ResourceStatutes::ERROR);
                }
            }
        }

        $this->dispatchEvent(ResourceEvents::POST_CREATES, new ResourceEvent($this, $list));

        return $list;
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
        $list = ResourceUtil::convertObjectsToResourceList($resources, $this->getClass());
        //TODO

        return $list;
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
        $list = ResourceUtil::convertObjectsToResourceList($resources, $this->getClass());
        //TODO

        return $list;
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
        $list = ResourceUtil::convertObjectsToResourceList($resources, $this->getClass());
        //TODO

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
     * @param object|null $resource The resource for auto commit or null for flush at the end
     *
     * @return ConstraintViolationList
     */
    protected function flushTransaction($resource = null)
    {
        $violations = new ConstraintViolationList();

        try {
            $this->flush($resource);

            if (null !== $this->connection && null === $resource) {
                $this->connection->commit();
            }
        } catch (\Exception $e) {
            if (null !== $this->connection && null === $resource) {
                $this->connection->rollback();
            }

            $messageTpl = $e->getMessage();
            $message = $messageTpl;

            if ($e instanceof DriverException) {
                $messageTpl = 'Database error code "%s"';
                $message = sprintf($messageTpl, $e->getSQLState());
            }

            $violations->add(new ConstraintViolation($message, $messageTpl, array(), null, null, null));
        }

        return $violations;
    }

    /**
     * Flush the data in database.
     *
     * @param object|null $resource The resource for auto commit or null for flush at the end
     */
    protected function flush($resource = null)
    {
        $this->om->flush();

        if (null !== $resource) {
            $this->om->detach($resource);
        } else {
            $this->om->clear();
        }
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
