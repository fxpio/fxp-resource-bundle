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

use Sonatra\Bundle\ResourceBundle\DependencyInjection\Compiler\ConverterPass;
use Sonatra\Bundle\ResourceBundle\DependencyInjection\Compiler\DomainPass;
use Sonatra\Bundle\ResourceBundle\DependencyInjection\Compiler\TranslatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author François Pluchino <francois.pluchino@sonatra.com>
 */
class SonatraResourceBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new TranslatorPass());
        $container->addCompilerPass(new ConverterPass());
        $container->addCompilerPass(new DomainPass());
    }
}
