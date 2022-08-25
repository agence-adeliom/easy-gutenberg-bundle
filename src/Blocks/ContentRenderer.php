<?php

namespace Adeliom\EasyGutenbergBundle\Blocks;

use Adeliom\EasyGutenbergBundle\Services\OEmbedService;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Uid\Uuid;
use Twig\Environment;

class ContentRenderer
{
    private BlockParser $parser;
    private OEmbedService $embedService;
    private BlockTypeRegistry $blockTypeRegistry;
    private Environment $twig;
    private FormFactoryInterface $formBuilder;

    public function __construct(BlockParser $parser, BlockTypeRegistry $blockTypeRegistry, OEmbedService $embedService, Environment $twig, FormFactoryInterface $formBuilder)
    {
        $this->parser = $parser;
        $this->blockTypeRegistry = $blockTypeRegistry;
        $this->embedService = $embedService;
        $this->twig = $twig;
        $this->formBuilder = $formBuilder;
    }

    public function render(string $content): string
    {
        $output = '';
        $blocks = $this->parser->parse($content);

        foreach ($blocks as $block) {
            $output .= $this->renderBlock($block);
        }

        return $output;
    }

    public function renderBlock(Block $block): string
    {
        $output = '';
        $index = 0;
        foreach ($block->innerContent as $innerContent) {
            $output .= is_string($innerContent)
                ? $innerContent
                : $block->innerBlocks[$index++]->render();
        }

        $blockType = $this->blockTypeRegistry->getBlockType($block->blockName);
        if ($blockType && $blockType::isDynamic()) {
            $output = $this->twig->render($blockType::getTemplate(), [
                'mode' => $block->getMode(),
                'attributes' => $block->attributes,
                'output' => $output,
            ]);
        }

        if ('core/embed' === $block->blockName) {
            $output = $this->embed($block, $output);
        }

        return $output;
    }

    public function renderEditor(Block $block): array
    {
        $output = '';
        $extra = [];
        $index = 0;
        foreach ($block->innerContent as $innerContent) {
            $output .= is_string($innerContent)
                ? $innerContent
                : $block->innerBlocks[$index++]->render();
        }

        $blockType = $this->blockTypeRegistry->getBlockType($block->blockName);
        if ($blockType && $blockType::isDynamic()) {
            $formData = $block->attributes['data'] ?? [];
            $form = $this->formBuilder->createNamed(md5(Uuid::v1()->toRfc4122()), get_class($blockType));
            if (!empty($formData)) {
                $form->submit($formData);
            }
            if ($form->isSubmitted()) {
                if (!$form->isValid()) {
                    $block->setMode('edit');
                }
            }
            if ($block->isMode('preview')) {
                $output = $this->twig->render($blockType::getTemplate(), [
                    'mode' => $block->getMode(),
                    'attributes' => $block->attributes,
                    'output' => $output,
                ]);
                $extra['assets'] = Helper::assets($blockType::configureAssets());
            } else {
                $output = $this->twig->render('@EasyGutenberg/form/edit.html.twig', [
                    'block' => $block,
                    'blockType' => $blockType,
                    'mode' => $block->getMode(),
                    'attributes' => $block->attributes,
                    'output' => $output,
                    'form' => $form->createView(),
                ]);
            }
        }

        if ('core/embed' === $block->blockName) {
            $output = $this->embed($block, $output);
        }

        return array_merge([
            'rendered' => $output,
            'attributes' => $block->attributes,
        ], $extra);
    }

    /**
     * @throws \Adeliom\EasyGutenbergBundle\Exceptions\OEmbedFetchException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function embed(Block $block, string $content): string
    {
        $embed = $this->embedService->parse($block->attributes['url']);

        return str_replace(
            htmlspecialchars($block->attributes['url']),
            $embed['html'],
            $content
        );
    }
}
