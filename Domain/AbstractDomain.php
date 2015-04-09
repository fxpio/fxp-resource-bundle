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
use Doctrine\ORM\EntityManagerInterface;
use Sonatra\Bundle\DefaultValueBundle\DefaultValue\ObjectFactoryInterface;
use Sonatra\Bundle\ResourceBundle\Event\ResourceEvent;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceList;
use Sonatra\Bundle\ResourceBundle\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * A abstract class for resource domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
abstract class AbstractDomain implements DomainInterface
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
        return $this->creates(array($resource), true)->get(0);
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
        return $this->updates(array($resource), true)->get(0);
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
        return $this->upserts(array($resource), true)->get(0);
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
        return $this->deletes(array($resource), true)->get(0);
    }

    /**
     * {@inheritdoc}
     */
    public function undelete($identifier)
    {
        return $this->undeletes(array($identifier), true)->get(0);
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

    /**
     * Persist the resources.
     *
     * Warning: It's recommended to limit the number of resources.
     *
     * @param FormInterface[]|object[] $resources  The list of object resource instance
     * @param bool                     $autoCommit Commit transaction for each resource or all
     *                                             (continue the action even if there is an error on a resource)
     * @param int                      $type       The type of persist action
     *
     * @return ResourceList
     */
    abstract protected function persist(array $resources, $autoCommit = false, $type);
}
