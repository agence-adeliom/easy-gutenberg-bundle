<?php

declare(strict_types=1);

namespace Adeliom\EasyGutenbergBundle\Twig;

use Adeliom\EasyGutenbergBundle\Blocks\Helper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EasyGutenbergExtension extends AbstractExtension
{
    /**
     * @return array<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('easy_gutenberg', [Helper::class, 'render'], ['is_safe' => ['js', 'html'], 'needs_context' => true, 'needs_environment' => true]),
            new TwigFunction('easy_gutenberg_assets', [Helper::class, 'render_assets'], ['is_safe' => ['js', 'html'], 'needs_context' => true, 'needs_environment' => true]),
        ];
    }
}
