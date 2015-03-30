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

use Sonatra\Bundle\ResourceBundle\Exception\InvalidArgumentException;

/**
 * Util for resource.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
abstract class ResourceUtil
{
    /**
     * Convert the object data of resource to resource list.
     *
     * @param object[] $objects      The resource object instance
     * @param string   $requireClass The require class name
     *
     * @return ResourceList
     *
     * @throws InvalidArgumentException When the instance object in the list is not an instance of the required class
     */
    public static function convertObjectsToResourceList(array $objects, $requireClass)
    {
        $list = new ResourceList();

        foreach ($objects as $i => $object) {
            static::validateObjectResource($object, $requireClass, $i);
            $list->add(new Resource((object) $object));
        }

        return $list;
    }

    /**
     * Validate the resource object.
     *
     * @param mixed  $object       The resource object
     * @param string $requireClass The required class
     * @param int    $i            The position of the object in the list
     *
     * @throws InvalidArgumentException When the object parameter is not an object
     * @throws InvalidArgumentException When the object instance is not an instance of the required class
     */
    public static function validateObjectResource($object, $requireClass, $i)
    {
        if (!is_object($object)) {
            $msg = sprintf('The resource at the position "%s" is not an object instance', $i);
            throw new InvalidArgumentException($msg);
        }

        if (!$object instanceof $requireClass) {
            $msg = sprintf('The object instance ("%s") is not an instance of "%s"', get_class($object), $requireClass);
            throw new InvalidArgumentException($msg);
        }
    }
}
