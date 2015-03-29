<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Fixtures\Entity;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo;

/**
 * Load TestBundle Foo data.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class LoadFooData implements FixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $foo1 = new Foo();
        $foo1->setName('Test 1');

        $manager->persist($foo1);
        $manager->flush();
    }
}
