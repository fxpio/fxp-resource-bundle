<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Bundle\ResourceBundle;

use Fxp\Bundle\ResourceBundle\DependencyInjection\Compiler\ConverterPass;
use Fxp\Bundle\ResourceBundle\DependencyInjection\Compiler\DomainPass;
use Fxp\Bundle\ResourceBundle\DependencyInjection\Compiler\TranslatorPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class FxpResourceBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $container->addCompilerPass(new TranslatorPass());
        $container->addCompilerPass(new ConverterPass());
        $container->addCompilerPass(new DomainPass());
    }
}
