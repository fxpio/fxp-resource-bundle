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
use Sonatra\Bundle\ResourceBundle\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
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
        return current($this->creates(array($resource)));
    }

    /**
     * {@inheritdoc}
     */
    public function creates(array $resources, $autoCommit = false, $skipError = false)
    {
        //TODO

        return $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function update($resource)
    {
        return current($this->updates(array($resource)));
    }

    /**
     * {@inheritdoc}
     */
    public function updates(array $resources, $autoCommit = false, $skipError = false)
    {
        //TODO

        return $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function upsert($resource)
    {
        return current($this->upserts(array($resource)));
    }

    /**
     * {@inheritdoc}
     */
    public function upserts(array $resources, $autoCommit = false, $skipError = false)
    {
        //TODO

        return $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($resource, $soft = true)
    {
        return current($this->deletes(array($resource), true));
    }

    /**
     * {@inheritdoc}
     */
    public function deletes(array $resources, $soft = true, $autoCommit = false, $skipError = false)
    {
        //TODO

        return $resources;
    }

    /**
     * {@inheritdoc}
     */
    public function undelete($identifier)
    {
        return current($this->deletes(array($identifier)));
    }

    /**
     * {@inheritdoc}
     */
    public function undeletes(array $identifiers, $autoCommit = false, $skipError = false)
    {
        //TODO

        return $identifiers;
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
}
