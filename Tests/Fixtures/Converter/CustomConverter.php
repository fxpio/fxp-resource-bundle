<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Tests\Fixtures\Converter;

use Sonatra\Bundle\ResourceBundle\Converter\ConverterInterface;

/**
 * Custom converter mock.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class CustomConverter implements ConverterInterface
{
    /**
     * @var string
     */
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function convert($content)
    {
        return $content;
    }
}
