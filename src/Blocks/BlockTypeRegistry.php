<?php

namespace Adeliom\EasyGutenbergBundle\Blocks;

class BlockTypeRegistry
{
    /** @var BlockTypeInterface[] */
    protected array $blockTypes = [];

    public function __construct(iterable $blocks)
    {
        foreach ($blocks as $block) {
            $this->blockTypes[] = $block;
        }

        uasort($this->blockTypes, static function ($a, $b) {
            return $a->getPosition() <=> $b->getPosition();
        });
    }

    public function register(string $name, array $attributes = [], callable $renderCallback = null)
    {
        $this->blockTypes[] = new BlockType($name, $attributes, $renderCallback);
    }

    /**
     * @return BlockTypeInterface[]|array
     */
    public function blockTypes(): array
    {
        return $this->blockTypes;
    }

    /**
     * @return BlockTypeInterface|null
     */
    public function getBlockType(string $name)
    {
        $arr = array_filter($this->blockTypes(), function ($blockType) use ($name) {
            return $blockType->getKey() === $name;
        });

        return array_shift($arr);
    }
}
