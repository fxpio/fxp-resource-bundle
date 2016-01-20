<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Domain;

use Sonatra\Bundle\ResourceBundle\Handler\ClosureFormConfigList;
use Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Form\FooType;

/**
 * Tests case for ClosureFormConfigList.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class ClosureFormConfigListTest extends \PHPUnit_Framework_TestCase
{
    public function testBasic()
    {
        $config = new ClosureFormConfigList(FooType::class);

        $this->assertTrue($config->isTransactional());
        $config->setTransactional(false);
        $this->assertFalse($config->isTransactional());
    }

    public function testConvertObjectsWithoutClosure()
    {
        $config = new ClosureFormConfigList(FooType::class);
        $list = array('mock');

        $this->assertNotSame($list, $config->convertObjects($list));
        $this->assertEquals(array(), $config->convertObjects($list));
    }

    public function testConvertObjectsWithClosure()
    {
        $config = new ClosureFormConfigList(FooType::class);
        $list = array('mock');

        $config->setObjectConverter(function (array $list) {
            return $list;
        });

        $this->assertEquals($list, $config->convertObjects($list));
    }
}
