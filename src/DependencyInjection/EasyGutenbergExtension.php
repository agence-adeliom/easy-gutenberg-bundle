<?php

declare(strict_types=1);

namespace Adeliom\EasyGutenbergBundle\DependencyInjection;

use Adeliom\EasyGutenbergBundle\Blocks\BlockTypeInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class EasyGutenbergExtension extends Extension
{
    /**
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(BlockTypeInterface::class)
            ->addTag('easy_gutenberg.block')
        ;

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');
    }

    public function getAlias(): string
    {
        return 'easy_gutenberg';
    }
}
