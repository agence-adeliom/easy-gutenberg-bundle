<?php

namespace Adeliom\EasyGutenbergBundle\Blocks;

class Block
{
    public string $blockName;

    public array $attributes;

    public array $innerBlocks;

    public string $innerHTML;

    public array $innerContent;

    public function __construct(
        string $blockName,
        array $attributes = [],
        array $innerBlocks = [],
        string $innerHTML = '',
        array $innerContent = []
    ) {
        $this->blockName = $blockName;
        $this->attributes = $attributes;
        $this->innerBlocks = $innerBlocks;
        $this->innerHTML = $innerHTML;
        $this->innerContent = $innerContent;
    }

    public function setMode(string $mode): void
    {
        $this->attributes['mode'] = $mode;
    }

    /**
     * @return string
     */
    public function getMode(): ?string
    {
        return $this->attributes['mode'] ?? null;
    }

    public function isMode(string $mode): bool
    {
        return ($this->attributes['mode'] ?? null) === $mode;
    }

    public static function fromArray(array $args): Block
    {
        $innerBlocks = [];
        foreach ($args['innerBlocks'] ?? [] as $innerBlock) {
            $innerBlocks[] = static::fromArray($innerBlock);
        }

        return new static(
            $args['blockName'] ?? '',
            $args['attrs'] ?? [],
            $innerBlocks,
            $args['innerHTML'] ?? '',
            $args['innerContent'] ?? []
        );
    }
}
