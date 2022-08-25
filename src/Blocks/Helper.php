<?php

declare(strict_types=1);

namespace Adeliom\EasyGutenbergBundle\Blocks;

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
    /**
     * @readonly
     */
    private \Twig\Environment $twig;
    /**
     * @readonly
     */
    private \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher;
    /**
     * @readonly
     */
    private BlockTypeRegistry $collection;
    private ContentRenderer $renderer;
    private BlockParser $parser;

    public function __construct(Environment $twig, EventDispatcherInterface $eventDispatcher, BlockTypeRegistry $collection, ContentRenderer $renderer, BlockParser $parser)
    {
        $this->twig = $twig;
        $this->eventDispatcher = $eventDispatcher;
        $this->collection = $collection;
        $this->renderer = $renderer;
        $this->parser = $parser;
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

        if (!empty($assets['css']) && !empty($assets['css'])) {
            $html .= "<style media='all'>";
            $assets['css'] = array_unique($assets['css']);
            foreach ($assets['css'] as $stylesheet) {
                $html .= "\n".sprintf('@import url(%s);', $stylesheet);
            }

            $html .= "\n</style>";
        }

        if (!empty($assets['js'])) {
            $assets['js'] = array_unique($assets['js']);
            foreach ($assets['js'] as $javascript) {
                $html .= "\n".sprintf('<script src="%s" type="text/javascript"></script>', $javascript);
            }
        }

        if (!empty($assets['webpack'])) {
            $assets['webpack'] = array_unique($assets['webpack']);
            foreach ($assets['webpack'] as $webpack) {
                try {
                    $html .= "\n".$this->twig->createTemplate(sprintf("{{ encore_entry_link_tags('%s') }}", $webpack))->render();
                    $html .= "\n".$this->twig->createTemplate(sprintf("{{ encore_entry_script_tags('%s') }}", $webpack))->render();
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

            $parseBlockEvent = new GenericEvent(null, ['attributes' => $attributes, 'block' => $block, 'assets' => $assets]);
            /**
             * @var GenericEvent $result;
             */
            $parseBlockEventResult = $this->eventDispatcher->dispatch($parseBlockEvent, 'easy_gutenberg.parse_block');

            /** @var Block $block */
            $block = $parseBlockEventResult->getArgument('block');
            $attributes = $parseBlockEventResult->getArgument('attributes');
            unset($attributes['mode']);
            $stats['attributes'] = $block->attributes = $attributes;
            $stats['assets'] = $parseBlockEventResult->getArgument('assets');

            $this->assets = array_merge_recursive($this->assets, $stats['assets']);
            $this->stopTracing($stats['id'], $stats);
        }

        /**
         * @var GenericEvent $result;
         */
        $preRenderBlocksEventResult = $this->eventDispatcher->dispatch(new GenericEvent(null, ['blocks' => $blocks]), 'easy_gutenberg.pre_render_blocks');
        $content = $this->parser->serialize($preRenderBlocksEventResult->getArgument('blocks'));
        $postRenderBlocksEventResult = $this->eventDispatcher->dispatch(new GenericEvent(null, ['content' => $content]), 'easy_gutenberg.post_render_blocks');

        return new Markup($this->renderer->render($postRenderBlocksEventResult->getArgument('content')), 'UTF-8');
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
