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

use Symfony\Component\Validator\ConstraintViolation;
use Sonatra\Bundle\ResourceBundle\Exception\ConstraintViolationException;
use Symfony\Component\Validator\ConstraintViolationList;

/**
 * Doctrine ORM error delete listener.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ErrorDeleteListener
{
    /**
     * @var bool
     */
    protected $useConstraint;

    public function __construct($useConstraint = false)
    {
        $this->useConstraint = $useConstraint;
    }

    /**
     * @throws \Exception When the entity does not deleted
     */
    public function onFlush()
    {
        if ($this->useConstraint) {
            $message = 'The entity does not deleted (violation exception)';
            $violation = new ConstraintViolation($message, $message, array(), null, null, null);
            $list = new ConstraintViolationList(array($violation));

            throw new ConstraintViolationException($list);
        }

        throw new \Exception('The entity does not deleted (exception)');
    }
}
