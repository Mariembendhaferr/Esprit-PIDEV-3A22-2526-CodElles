<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class TextRazorSentimentAnalyzer
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?string $apiKey = null,
    ) {
    }

    public function scoreText(string $text): ?float
    {
        $text = trim($text);
        if ('' === $text) {
            return null;
        }

        if (empty($this->apiKey)) {
            return $this->fallbackScore($text);
        }

        try {
            $response = $this->httpClient->request('POST', 'https://api.textrazor.com/', [
                'headers' => [
                    'x-textrazor-key' => $this->apiKey,
                ],
                'body' => [
                    'text' => $text,
                    'extractors' => 'entities,topics,words,phrases,sentiment',
                ],
                'timeout' => 12,
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode < 200 || $statusCode >= 300) {
                return $this->fallbackScore($text);
            }

            /** @var array<string, mixed> $data */
            $data = $response->toArray(false);

            $score = $this->extractScore($data);
            if (null !== $score) {
                return max(-1.0, min(1.0, $score));
            }
        } catch (ExceptionInterface) {
            // Silent fallback to keep dashboard available.
        }

        return $this->fallbackScore($text);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function extractScore(array $payload): ?float
    {
        $response = $payload['response'] ?? null;
        if (!\is_array($response)) {
            return null;
        }

        // Field shape can vary by extractor/version.
        if (isset($response['sentimentScore']) && \is_numeric($response['sentimentScore'])) {
            return (float) $response['sentimentScore'];
        }

        $documents = $response['documents'] ?? null;
        if (\is_array($documents)) {
            $scores = [];
            foreach ($documents as $doc) {
                if (\is_array($doc) && isset($doc['sentimentScore']) && \is_numeric($doc['sentimentScore'])) {
                    $scores[] = (float) $doc['sentimentScore'];
                }
            }
            if ([] !== $scores) {
                return array_sum($scores) / \count($scores);
            }
        }

        return null;
    }

    private function fallbackScore(string $text): float
    {
        $lower = mb_strtolower($text);

        $positive = [
            'excellent', 'parfait', 'super', 'genial', 'génial', 'bien', 'top', 'satisfait',
            'recommande', 'merci', 'great', 'good', 'amazing', 'awesome', 'perfect', 'recommended',
        ];
        $negative = [
            'mauvais', 'nul', 'horrible', 'retard', 'jamais', 'decu', 'déçu', 'probleme', 'problème',
            'arnaque', 'bad', 'very bad', 'worst', 'awful', 'terrible', 'not good', 'do not recommend',
            "don't recommend", 'never again', 'poor',
        ];

        $p = 0;
        foreach ($positive as $word) {
            if (str_contains($lower, $word)) {
                ++$p;
            }
        }

        $n = 0;
        foreach ($negative as $word) {
            if (str_contains($lower, $word)) {
                ++$n;
            }
        }

        // Negation patterns should strongly impact polarity.
        if (str_contains($lower, 'do not recommend') || str_contains($lower, "don't recommend")) {
            $n += 2;
        }
        if (str_contains($lower, 'not good') || str_contains($lower, 'not recommend')) {
            ++$n;
        }

        if (0 === $p && 0 === $n) {
            return 0.0;
        }

        return max(-1.0, min(1.0, ($p - $n) / max(1, $p + $n)));
    }
}

