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

use Doctrine\Common\Persistence\ManagerRegistry;
use Sonatra\Bundle\DefaultValueBundle\DefaultValue\ObjectFactoryInterface;
use Sonatra\Bundle\ResourceBundle\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Domain manager.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainManager implements DomainManagerInterface
{
    /**
     * @var DomainInterface[]
     */
    protected $domains;

    /**
     * @var ManagerRegistry
     */
    protected $or;

    /**
     * @var EventDispatcherInterface
     */
    protected $ed;

    /**
     * @var ObjectFactoryInterface
     */
    protected $of;

    /**
     * Constructor.
     *
     * @param DomainInterface[]        $domains The resource domains
     * @param ManagerRegistry          $or      The doctrine object manager
     * @param EventDispatcherInterface $ed      The event dispatcher
     * @param ObjectFactoryInterface   $of      The object factory
     */
    public function __construct(array $domains, ManagerRegistry $or,
                                EventDispatcherInterface $ed, ObjectFactoryInterface $of)
    {
        $this->domains = array();
        $this->or = $or;
        $this->ed = $ed;
        $this->of = $of;

        foreach ($domains as $domain) {
            $this->add($domain);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function has($class)
    {
        return isset($this->domains[$class]);
    }

    /**
     * {@inheritdoc}
     */
    public function add(DomainInterface $domain)
    {
        $domain->setObjectManager($this->or->getManagerForClass($domain->getClass()));
        $domain->setEventDispatcher($this->ed);
        $domain->setObjectFactory($this->of);
        $this->domains[$domain->getClass()] = $domain;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($class)
    {
        unset($this->domains[$class]);
    }

    /**
     * {@inheritdoc}
     */
    public function all()
    {
        return $this->domains;
    }

    /**
     * {@inheritdoc}
     */
    public function get($class)
    {
        if ($this->has($class)) {
            return $this->domains[$class];
        }

        throw new InvalidArgumentException(sprintf('The resource domain for "%s" class is not managed', $class));
    }
}
