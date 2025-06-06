<?php

declare(strict_types=1);

namespace Adeliom\EasyGutenbergBundle\Blocks;

use Adeliom\EasyBlockBundle\Event\ParseBlockEvent;
use Adeliom\EasyBlockBundle\Event\PostBlockEvent;
use Adeliom\EasyBlockBundle\Event\PreBlockEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\SyntaxError;
use Twig\Markup;

class Helper
{
    /**
     * This property is a state variable holdings all assets used by the block for the current PHP request
     * It is used to correctly render the javascripts and stylesheets tags on the main layout.
     */
    private array $assets = [
        'js' => [],
        'css' => [],
        'webpack' => [],
    ];

    private array $traces = [];

    public function __construct(
        private readonly Environment $twig,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly BlockTypeRegistry $collection,
        private readonly ContentRenderer $renderer,
        private readonly BlockParser $parser,
    ) {
    }

    public function render_assets(): array|string
    {
        return self::assets($this->assets);
    }

    /**
     * @return array<mixed>|string
     */
    public static function assets(?array $assets): array|string
    {
        $html = '';

        if (! empty($assets['css']) && ! empty($assets['css'])) {
            $html .= "<style media='all'>";
            $assets['css'] = array_unique($assets['css']);
            foreach ($assets['css'] as $stylesheet) {
                $html .= "\n" . sprintf('@import url(%s);', $stylesheet);
            }

            $html .= "\n</style>";
        }

        if (! empty($assets['js'])) {
            $assets['js'] = array_unique($assets['js']);
            foreach ($assets['js'] as $javascript) {
                $html .= "\n" . sprintf('<script src="%s" type="text/javascript"></script>', $javascript);
            }
        }

        if (! empty($assets['webpack'])) {
            $assets['webpack'] = array_unique($assets['webpack']);
            foreach ($assets['webpack'] as $webpack) {
                try {
                    $html .= "\n" . $this->twig->createTemplate(sprintf("{{ encore_entry_link_tags('%s') }}", $webpack))->render();
                    $html .= "\n" . $this->twig->createTemplate(sprintf("{{ encore_entry_script_tags('%s') }}", $webpack))->render();
                } catch (LoaderError|SyntaxError $exception) {
                    $html .= '';
                }
            }
        }

        return $html;
    }

    /**
     * Returns the rendering traces.
     */
    public function getTraces(): array
    {
        return $this->traces;
    }

    public function render(Environment $env, array $context, string $content, $extra = [])
    {
        $blocks = $this->parser->parse($content);
        foreach ($blocks as $block) {
            $blockMetas = $this->collection->getBlockType($block->blockName);
            $stats = $this->startTracing($block);
            $assets = $blockMetas::configureAssets();
            $attributes = $block->attributes;

            $parseBlockEvent = new ParseBlockEvent(['attributes' => $attributes, 'block' => $block, 'assets' => $assets]);
            /**
             * @var GenericEvent $result ;
             */
            $parseBlockEventResult = $this->eventDispatcher->dispatch($parseBlockEvent);

            /** @var Block $block */
            $block = $parseBlockEventResult->getSetting('block');
            $attributes = $parseBlockEventResult->getSetting('attributes');
            unset($attributes['mode']);
            $stats['attributes'] = $block->attributes = $attributes;
            $stats['assets'] = $parseBlockEventResult->getSetting('assets');

            $this->assets = array_merge_recursive($this->assets, $stats['assets']);
            $this->stopTracing($stats['id'], $stats);
        }

        /**
         * @var GenericEvent $result ;
         */
        $preRenderBlocksEventResult = $this->eventDispatcher->dispatch(new PreBlockEvent($blocks));
        $content = $this->parser->serialize($preRenderBlocksEventResult->getBlocks());
        $postRenderBlocksEventResult = $this->eventDispatcher->dispatch(new PostBlockEvent($content));

        return new Markup($this->renderer->render($postRenderBlocksEventResult->getContent()), 'UTF-8');
    }

    private function startTracing(Block $block): array
    {
        return [
            'id' => uniqid('block-', true),
            'name' => $block->blockName,
            'attributes' => $block->attributes,
            'assets' => [
                'js' => [],
                'css' => [],
                'webpack' => [],
            ],
        ];
    }

    private function stopTracing($id, array $stats): void
    {
        $this->traces[$id] = $stats;
    }
}
