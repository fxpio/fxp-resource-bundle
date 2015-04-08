<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Listener;

/**
 * Doctrine ORM error delete listener.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ErrorDeleteListener
{
    /**
     * @throws \Exception When the entity does not deleted
     */
    public function onFlush()
    {
        throw new \Exception('The entity does not deleted');
    }
}
