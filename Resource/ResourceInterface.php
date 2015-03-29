<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Resource;

use Symfony\Component\Validator\ConstraintViolationListInterface;

/**
 * Resource interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
interface ResourceInterface
{
    /**
     * Get the status of action by the resource domain.
     *
     * @return string
     */
    public function getStatus();

    /**
     * Get the resource instance.
     *
     * @return object
     */
    public function getResource();

    /**
     * Get the list of errors.
     *
     * @return ConstraintViolationListInterface
     */
    public function getErrors();
}
