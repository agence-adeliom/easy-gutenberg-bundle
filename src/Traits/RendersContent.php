<?php

namespace Adeliom\EasyGutenbergBundle\Traits;

use Adeliom\EasyGutenbergBundle\Blocks\ContentRenderer;

trait RendersContent
{
    protected $contentProperty = 'content';

    abstract protected function getContentRenderer(): ContentRenderer;

    public function render(string $property = null): string
    {
        $property = $property ?: $this->contentProperty;
        $renderer = $this->getContentRenderer();
        $content = $this->$property;

        return $renderer->render(is_string($content) ? $content : '');
    }
}
