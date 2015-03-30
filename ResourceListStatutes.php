<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle;

use Sonatra\Bundle\ResourceBundle\Exception\ClassNotInstantiableException;

/**
 * The action statutes for the list of resource domains.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
final class ResourceListStatutes
{
    /**
     * The ResourceStatutes::SUCCESSFULLY status is used when the all resources in the
     * list has been executed successfully.
     *
     * This status is used in Sonatra\Bundle\ResourceBundle\Event\ResourceEvent
     * and Sonatra\Bundle\ResourceBundle\Domain\DomainInterface instances.
     */
    const SUCCESSFULLY = 'successfully';

    /**
     * The ResourceStatutes::PARTIAL_SUCCESSFULLY status is used when part of the
     * resource has been executed successfully.
     *
     * This status is used in Sonatra\Bundle\ResourceBundle\Event\ResourceEvent
     * and Sonatra\Bundle\ResourceBundle\Domain\DomainInterface instances.
     */
    const PARTIAL_SUCCESSFULLY = 'partial successfully';

    /**
     * The ResourceStatutes::ERRORS status is used when the all resources in the
     * list has errors.
     *
     * This status is used in Sonatra\Bundle\ResourceBundle\Event\ResourceEvent
     * and Sonatra\Bundle\ResourceBundle\Domain\DomainInterface instances.
     */
    const ERRORS = 'errors';

    /**
     * Constructor.
     *
     * @throws ClassNotInstantiableException
     */
    public function __construct()
    {
        throw new ClassNotInstantiableException(__CLASS__);
    }
}
