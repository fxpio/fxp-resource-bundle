<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Resource;

use Sonatra\Bundle\ResourceBundle\Resource\ResourceList;
use Sonatra\Bundle\ResourceBundle\ResourceListStatutes;
use Sonatra\Bundle\ResourceBundle\ResourceStatutes;

/**
 * Tests case for resource list.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ResourceListTest extends \PHPUnit_Framework_TestCase
{
    public function getData()
    {
        return array(
            array(ResourceListStatutes::SUCCESSFULLY, array()),
            array(ResourceListStatutes::SUCCESSFULLY, array(ResourceStatutes::CREATED, ResourceStatutes::CREATED)),
            array(ResourceListStatutes::SUCCESSFULLY, array(ResourceStatutes::UPDATED, ResourceStatutes::UPDATED)),
            array(ResourceListStatutes::SUCCESSFULLY, array(ResourceStatutes::DELETED, ResourceStatutes::DELETED)),
            array(ResourceListStatutes::SUCCESSFULLY, array(ResourceStatutes::UNDELETED, ResourceStatutes::UNDELETED)),
            array(ResourceListStatutes::SUCCESSFULLY, array(ResourceStatutes::CREATED, ResourceStatutes::UPDATED)),
            array(ResourceListStatutes::SUCCESSFULLY, array(ResourceStatutes::DELETED, ResourceStatutes::UNDELETED)),
            array(ResourceListStatutes::SUCCESSFULLY, array(ResourceStatutes::CREATED, ResourceStatutes::UPDATED, ResourceStatutes::DELETED, ResourceStatutes::UNDELETED)),
            array(ResourceListStatutes::CANCEL, array(ResourceStatutes::CANCELED, ResourceStatutes::CANCELED)),
            array(ResourceListStatutes::ERROR, array(ResourceStatutes::ERROR, ResourceStatutes::ERROR)),
            array(ResourceListStatutes::PENDING, array(ResourceStatutes::PENDING, ResourceStatutes::PENDING)),
            array(ResourceListStatutes::MIXED, array(ResourceStatutes::CREATED, ResourceStatutes::PENDING)),
            array(ResourceListStatutes::MIXED, array(ResourceStatutes::CREATED, ResourceStatutes::CANCELED)),
            array(ResourceListStatutes::MIXED, array(ResourceStatutes::CREATED, ResourceStatutes::ERROR)),
        );
    }

    /**
     * @dataProvider getData
     *
     * @param string $valid            The valid status of resource list
     * @param array  $resourceStatutes The status of resource in list
     */
    public function testStatus($valid, array $resourceStatutes)
    {
        $resources = array();

        foreach ($resourceStatutes as $rStatus) {
            $resource = $this->getMock('Sonatra\Bundle\ResourceBundle\Resource\ResourceInterface');
            $resource->expects($this->any())
                ->method('getStatus')
                ->will($this->returnValue($rStatus));

            $resources[] = $resource;
        }

        $list = new ResourceList($resources);

        $this->assertSame($valid, $list->getStatus());
    }
}
