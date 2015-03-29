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
use Doctrine\Common\Persistence\ObjectManager;
use Sonatra\Bundle\DefaultValueBundle\DefaultValue\ObjectFactoryInterface;
use Sonatra\Bundle\ResourceBundle\Exception\InvalidArgumentException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var array
     */
    protected $cache;

    /**
     * Constructor.
     *
     * @param DomainInterface[]        $domains   The resource domains
     * @param ManagerRegistry          $or        The doctrine object manager
     * @param EventDispatcherInterface $ed        The event dispatcher
     * @param ObjectFactoryInterface   $of        The object factory
     * @param ValidatorInterface       $validator The validator
     */
    public function __construct(array $domains, ManagerRegistry $or,
                                EventDispatcherInterface $ed, ObjectFactoryInterface $of,
                                ValidatorInterface $validator)
    {
        $this->domains = array();
        $this->or = $or;
        $this->ed = $ed;
        $this->of = $of;
        $this->validator = $validator;
        $this->cache = array();

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
        $domain->setValidator($this->validator);
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
        if (isset($this->cache[$class])) {
            return $this->domains[$this->cache[$class]];
        }

        $getClass = $class;
        $manager = $this->getManager($class);
        $class = $manager->getClassMetadata($class)->getName();

        if ($this->has($class)) {
            $this->cache[$getClass] = $class;

            return $this->domains[$class];
        }

        throw new InvalidArgumentException(sprintf('The resource domain for "%s" class is not managed', $class));
    }

    /**
     * Get the doctrine object manager of the class.
     *
     * @param string $class The class name or doctrine shortcut class name
     *
     * @return ObjectManager
     *
     * @throws InvalidArgumentException When the class is not registered in doctrine
     */
    protected function getManager($class)
    {
        $manager = $this->or->getManagerForClass($class);

        if (null !== $manager) {
            return $manager;
        }

        throw new InvalidArgumentException(sprintf('The "%s" class is not registered in doctrine', $class));
    }
}
