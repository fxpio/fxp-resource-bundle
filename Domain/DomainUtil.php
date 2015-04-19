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

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\DBAL\Exception\DriverException;
use Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface;
use Sonatra\Bundle\ResourceBundle\ResourceEvents;
use Sonatra\Bundle\ResourceBundle\ResourceStatutes;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\ConstraintViolation;

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
     * @param bool            $debug     The debug mode
     *
     * @return string
     */
    public static function extractDriverExceptionMessage(DriverException $exception, $debug = false)
    {
        $message = 'Database error';

        if ($debug && null !== $exception->getPrevious()) {
            $prevMessage = static::getFirstException($exception)->getMessage();
            $pos = strpos($prevMessage, ':');

            if ($pos > 0 && 0 === strpos($prevMessage, 'SQLSTATE[')) {
                $message .= ': '.trim(substr($prevMessage, $pos+1));
            }
        }

        return $message;
    }

    /**
     * Get the value of resource identifier.
     *
     * @param ObjectManager $om     The doctrine object manager
     * @param object        $object The resource object
     *
     * @return int|string|null
     */
    public static function getIdentifier(ObjectManager $om, $object)
    {
        $propertyAccess = PropertyAccess::createPropertyAccessor();
        $meta = $om->getClassMetadata(get_class($object));
        $ids = $meta->getIdentifier();
        $value = null;

        foreach ($ids as $id) {
            $idVal = $propertyAccess->getValue($object, $id);

            if (null !== $idVal) {
                $value = $idVal;
                break;
            }
        }

        return $value;
    }

    /**
     * Get the name of identifier.
     *
     * @param ObjectManager $om        The doctrine object manager
     * @param string        $className The class name
     *
     * @return string
     */
    public static function getIdentifierName(ObjectManager $om, $className)
    {
        $meta = $om->getClassMetadata($className);
        $ids = $meta->getIdentifier();

        return implode('', $ids);
    }

    /**
     * Get the event names of persist action.
     *
     * @param int $type The type of persist
     *
     * @return array The list of pre event name and post event name
     */
    public static function getEventNames($type)
    {
        $names = array(ResourceEvents::PRE_UPSERTS, ResourceEvents::POST_UPSERTS);

        if (Domain::TYPE_CREATE === $type) {
            $names = array(ResourceEvents::PRE_CREATES, ResourceEvents::POST_CREATES);
        } elseif (Domain::TYPE_UPDATE === $type) {
            $names = array(ResourceEvents::PRE_UPDATES, ResourceEvents::POST_UPDATES);
        } elseif (Domain::TYPE_UNDELETE === $type) {
            $names = array(ResourceEvents::PRE_UNDELETES, ResourceEvents::POST_UNDELETES);
        }

        return $names;
    }

    /**
     * Add the error in resource.
     *
     * @param ResourceInterface $resource The resource
     * @param string            $message  The error message
     */
    public static function addResourceError(ResourceInterface $resource, $message)
    {
        $resource->setStatus(ResourceStatutes::ERROR);
        $resource->getErrors()->add(new ConstraintViolation($message, $message, array(), null, null, null));
    }

    /**
     * Extract the identifier that are not a object.
     *
     * @param array $identifiers The list containing identifier or object
     * @param array $objects     The real objects (by reference)
     *
     * @return array The identifiers that are not a object
     */
    public static function extractIdentifierInObjectList(array $identifiers, array &$objects)
    {
        $searchIds = array();

        foreach ($identifiers as $identifier) {
            if (is_object($identifier)) {
                $objects[] = $identifier;
                continue;
            }
            $searchIds[] = $identifier;
        }

        return $searchIds;
    }

    /**
     * Generate the short name of domain with the class name.
     *
     * @param string $class
     *
     * @return string
     */
    public static function generateShortName($class)
    {
        $pos = strrpos($class, '\\');

        return substr($class, $pos + 1);
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
