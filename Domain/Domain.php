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
use Sonatra\Bundle\DefaultValueBundle\DefaultValue\ObjectFactoryInterface;
use Sonatra\Bundle\ResourceBundle\Exception\InvalidConfigurationException;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
        $this->eventPrefix = $this->getEventPrefix($class);
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
    public function creates(array $resources)
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
    public function updates(array $resources)
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
    public function upserts(array $resources)
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
    public function deletes(array $resources, $soft = true)
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
    public function undeletes(array $identifiers)
    {
        //TODO

        return $identifiers;
    }

    /**
     * Dispatch the event.
     *
     * @param string $name  The event name
     * @param Event  $event The event
     *
     * @return Event
     */
    protected function dispatchEvent($name, Event $event)
    {
        $name = $this->eventPrefix.$name;

        return $this->ed->dispatch($name, $event);
    }

    private function getEventPrefix($class)
    {
        //TODO
        return $class;
    }
}
