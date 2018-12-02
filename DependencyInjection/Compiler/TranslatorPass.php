<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Bundle\ResourceBundle\DependencyInjection\Compiler;

use Fxp\Component\Resource\ResourceInterface;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Finder\Finder;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class TranslatorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('translator.default')) {
            return;
        }

        $translator = $container->getDefinition('translator.default');
        $ref = new \ReflectionClass(ResourceInterface::class);
        $dir = realpath(\dirname($ref->getFileName()).'/Resources/translations');

        $container->addResource(new DirectoryResource($dir));

        $optionsArgumentIndex = \count($translator->getArguments()) - 1;
        $options = $translator->getArgument($optionsArgumentIndex);
        $options['resource_files'] = isset($options['resource_files']) ? $options['resource_files'] : [];

        /* @var Finder|\SplFileInfo[] $finder */
        $finder = Finder::create()
            ->files()
            ->filter(function (\SplFileInfo $file) {
                return 2 === substr_count($file->getBasename(), '.') && preg_match('/\.\w+$/', $file->getBasename());
            })
            ->in([$dir])
        ;

        foreach ($finder as $file) {
            list(, $locale) = explode('.', $file->getBasename(), 3);
            $options['resource_files'][$locale] = isset($options['resource_files'][$locale]) ? $options['resource_files'][$locale] : [];

            array_unshift($options['resource_files'][$locale], (string) $file);
        }

        $translator->replaceArgument($optionsArgumentIndex, $options);
    }
}
