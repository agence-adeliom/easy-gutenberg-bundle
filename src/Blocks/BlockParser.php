<?php

namespace Adeliom\EasyGutenbergBundle\Blocks;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Uid\Uuid;
use WP_Block_Parser;

class BlockParser
{
    protected WP_Block_Parser $parser;
    protected FormFactoryInterface $formBuilder;
    protected BlockTypeRegistry $blockTypeRegistry;

    public function __construct(FormFactoryInterface $formBuilder, BlockTypeRegistry $blockTypeRegistry)
    {
        $this->parser = new \WP_Block_Parser();
        $this->formBuilder = $formBuilder;
        $this->blockTypeRegistry = $blockTypeRegistry;
    }

    /**
     * @return Block[]
     */
    public function parse(?string $content = null): array
    {
        $blocks = $this->parser->parse($content);

        return array_map(static function ($block) {
            return Block::fromArray((array) $block);
        }, $blocks);
    }

    public function validate(Block $block)
    {
        $blockType = $this->blockTypeRegistry->getBlockType($block->blockName);
        $errors = null;
        if ($blockType && $blockType::isDynamic()) {
            $formData = $block->attributes['data'] ?? [];
            $form = $this->formBuilder->createNamed(md5(Uuid::v1()->toRfc4122()), get_class($blockType));
            if (!empty($formData)) {
                $form->submit($formData);
            }

            if ($form->isSubmitted()) {
                if (!$form->isValid()) {
                    $errors = $form->getErrors();
                    $block->setMode('edit');
                }
            }
        }

        return [
            'errors' => $errors,
            'block' => $block,
        ];
    }

    /**
     * @param Block[]|Block $block
     */
    public function serialize($block): string
    {
        if (is_iterable($block)) {
            return implode('', array_map(function ($blockObject) {
                return $this->serializeBlock($blockObject);
            }, $block));
        }

        return $this->serializeBlock($block);
    }

    private function serializeBlock(Block $block)
    {
        $block_content = '';
        $index = 0;
        foreach ($block->innerContent as $chunk) {
            $block_content .= is_string($chunk) ? $chunk : $this->serializeBlock($block->innerBlocks[$index++]);
        }

        return $this->getCommentDelimitedBlockContent(
            $block->blockName,
            $block->attributes,
            $block_content
        );
    }

    private function getCommentDelimitedBlockContent(string $blockName, array $blockAttributes = [], string $blockContent = ''): string
    {
        $serializedBlockName = self::stripCoreBlockNamespace($blockName);
        $serializedAttributes = empty($blockAttributes) ? '' : self::serializeBlockAttributes($blockAttributes).' ';

        if (empty($blockContent)) {
            return sprintf('<!-- wp:%s %s/-->', $serializedBlockName, $serializedAttributes);
        }

        return sprintf(
            '<!-- wp:%s %s-->%s<!-- /wp:%s -->',
            $serializedBlockName,
            $serializedAttributes,
            $blockContent,
            $serializedBlockName
        );
    }

    private static function stripCoreBlockNamespace($blockName = null)
    {
        if (is_string($blockName) && 0 === strpos($blockName, 'core/')) {
            return substr($blockName, 5);
        }

        return $blockName;
    }

    private static function serializeBlockAttributes($block_attributes)
    {
        $encoded_attributes = self::safeJsonEncode($block_attributes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $encoded_attributes = preg_replace('/--/', '\\u002d\\u002d', $encoded_attributes);
        $encoded_attributes = preg_replace('/</', '\\u003c', $encoded_attributes);
        $encoded_attributes = preg_replace('/>/', '\\u003e', $encoded_attributes);
        $encoded_attributes = preg_replace('/&/', '\\u0026', $encoded_attributes);
        // Regex: /\\"/
        $encoded_attributes = preg_replace('/\\\\"/', '\\u0022', $encoded_attributes);

        return $encoded_attributes;
    }

    private static function safeJsonEncode($data, $options = 0, $depth = 512)
    {
        $json = json_encode($data, $options, $depth);
        // If json_encode() was successful, no need to do more sanity checking.
        if (false !== $json) {
            return $json;
        }
        try {
            $data = self::jsonSanityCheck($data, $depth);
        } catch (\Exception $e) {
            return false;
        }

        return json_encode($data, $options, $depth);
    }

    private static function jsonSanityCheck($data, $depth)
    {
        if ($depth < 0) {
            throw new \Exception('Reached depth limit');
        }

        if (is_array($data)) {
            $output = [];
            foreach ($data as $id => $el) {
                // Don't forget to sanitize the ID!
                if (is_string($id)) {
                    $clean_id = self::jsonConvertString($id);
                } else {
                    $clean_id = $id;
                }

                // Check the element type, so that we're only recursing if we really have to.
                if (is_array($el) || is_object($el)) {
                    $output[$clean_id] = self::jsonSanityCheck($el, $depth - 1);
                } elseif (is_string($el)) {
                    $output[$clean_id] = self::jsonConvertString($el);
                } else {
                    $output[$clean_id] = $el;
                }
            }
        } elseif (is_object($data)) {
            $output = new \stdClass();
            foreach ($data as $id => $el) {
                if (is_string($id)) {
                    $clean_id = self::jsonConvertString($id);
                } else {
                    $clean_id = $id;
                }

                if (is_array($el) || is_object($el)) {
                    $output->$clean_id = self::jsonSanityCheck($el, $depth - 1);
                } elseif (is_string($el)) {
                    $output->$clean_id = self::jsonConvertString($el);
                } else {
                    $output->$clean_id = $el;
                }
            }
        } elseif (is_string($data)) {
            return self::jsonConvertString($data);
        } else {
            return $data;
        }

        return $output;
    }

    private static function jsonConvertString($string)
    {
        static $use_mb = null;
        if (is_null($use_mb)) {
            $use_mb = function_exists('mb_convert_encoding');
        }

        if ($use_mb) {
            $encoding = mb_detect_encoding($string, mb_detect_order(), true);
            if ($encoding) {
                return mb_convert_encoding($string, 'UTF-8', $encoding);
            } else {
                return mb_convert_encoding($string, 'UTF-8', 'UTF-8');
            }
        } elseif (function_exists('iconv')) {
            return iconv('utf-8', 'utf-8', $string);
        } else {
            return $string;
        }
    }
}
