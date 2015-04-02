<?php

/*
 * This file is part of the Sonatra package.
 *
 * (c) François Pluchino <francois.pluchino@sonatra.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonatra\Bundle\ResourceBundle\Converter;

use Sonatra\Bundle\ResourceBundle\Exception\InvalidConverterException;

/**
 * A request content converter interface.
 *
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
interface ConverterInterface
{
    /**
     * Get the name of the conversion.
     *
     * @return string
     */
    public function getName();

    /**
     * Convert the string content to array.
     *
     * @param string $content
     *
     * @return array
     *
     * @throws InvalidConverterException When the data can not be converted
     */
    public function convert($content);
}
