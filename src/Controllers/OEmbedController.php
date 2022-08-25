<?php

namespace Adeliom\EasyGutenbergBundle\Controllers;

use Adeliom\EasyGutenbergBundle\Services\OEmbedService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class OEmbedController extends AbstractController
{
    public function show(Request $request, OEmbedService $oembed): JsonResponse
    {
        try {
            return $this->json($oembed->parse(
                $request->get('url')
            ));
        } catch (\Exception|InvalidArgumentException $e) {
            return $this->json([]);
        }
    }
}
