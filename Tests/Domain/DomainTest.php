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

use Sonatra\Bundle\ResourceBundle\Domain\Domain;

/**
 * Tests case for Domain.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class DomainTest extends \PHPUnit_Framework_TestCase
{
    public function getShortNames()
    {
        return array(
            array(null,              'Foo'),
            array('CustomShortName', 'CustomShortName'),
        );
    }

    /**
     * @dataProvider getShortNames
     *
     * @param string|null $shortName      The short name of domain
     * @param string      $validShortName The valid short name of domain
     */
    public function testShortName($shortName, $validShortName)
    {
        $domain = new Domain('Sonatra\Bundle\ResourceBundle\Tests\Functional\Fixture\Bundle\TestBundle\Entity\Foo', $shortName);

        $this->assertSame($validShortName, $domain->getShortName());
    }
}
