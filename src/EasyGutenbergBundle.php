<?php

declare(strict_types=1);

namespace Adeliom\EasyGutenbergBundle;

use Adeliom\EasyGutenbergBundle\DependencyInjection\EasyGutenbergExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EasyGutenbergBundle extends Bundle
{
    /**
     * @return ExtensionInterface|null The container extension
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new EasyGutenbergExtension();
    }
}
