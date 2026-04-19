<?php

namespace App\Controller;

use App\Service\LaraTranslateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class PublicTranslationController extends AbstractController
{
    #[Route('/translate', name: 'app_public_translate', methods: ['POST'])]
    public function translate(Request $request, LaraTranslateService $translator): JsonResponse
    {
        /** @var array<string, mixed> $payload */
        $payload = json_decode($request->getContent(), true) ?? [];
        $text = trim((string) ($payload['text'] ?? ''));
        $target = strtolower(trim((string) ($payload['target'] ?? '')));
        $legacySource = strtolower(trim((string) ($payload['source'] ?? '')));

        if ('' === $text || !\in_array($target, ['fr', 'en'], true)) {
            return $this->json(['ok' => false, 'message' => 'Paramètres invalides.'], 400);
        }

        if ('' !== $legacySource && \in_array($legacySource, ['fr', 'en'], true)) {
            $translated = $translator->translate($text, $legacySource, $target);
        } else {
            $translated = $translator->translateToLanguage($text, $target);
        }

        return $this->json([
            'ok' => true,
            'translated' => $translated,
        ]);
    }
}

