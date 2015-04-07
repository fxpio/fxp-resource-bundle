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

use Doctrine\DBAL\Exception\DriverException;

/**
 * Util for domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
abstract class DomainUtil
{
    /**
     * Format pdo driver exception.
     *
     * @param DriverException $exception The exception
     *
     * @return string
     */
    public static function extractDriverExceptionMessage(DriverException $exception)
    {
        $message = 'Database invalid query';

        if (null !== $exception->getPrevious()) {
            $prevMessage = static::getFirstException($exception)->getMessage();
            $pos = strpos($prevMessage, ':');

            if ($pos > 0 && 0 === strpos($prevMessage, 'SQLSTATE[')) {
                $message = trim(substr($prevMessage, $pos + 1));
            }
        }

        return $message;
    }

    /**
     * Get the initial exception.
     *
     * @param \Exception $exception
     *
     * @return \Exception
     */
    protected static function getFirstException(\Exception $exception)
    {
        if (null !== $exception->getPrevious()) {
            return static::getFirstException($exception->getPrevious());
        }

        return $exception;
    }
}
