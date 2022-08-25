<?php

declare(strict_types=1);

namespace Adeliom\EasyGutenbergBundle\DataCollector;

use Adeliom\EasyGutenbergBundle\Blocks\Helper;
use Symfony\Bundle\FrameworkBundle\DataCollector\AbstractDataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class GutenbergCollector extends AbstractDataCollector
{
    protected Helper $blockHelper;

    public function __construct(Helper $blockHelper)
    {
        $this->blockHelper = $blockHelper;
    }

    public function collect(Request $request, Response $response, ?\Throwable $exception = null): void
    {
        $this->data['blocks'] = $this->blockHelper->getTraces();
    }

    public function getBlocks(): array
    {
        return $this->data['blocks'] ?: [];
    }

    public function getName(): string
    {
        return self::class;
    }
}
