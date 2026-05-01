<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ImageModerationService
{
    private const TRAVEL_TAGS = [
        'travel', 'tourism', 'vacation', 'holiday', 'trip',
        'beach', 'ocean', 'sea', 'lake', 'river', 'waterfall',
        'mountain', 'landscape', 'nature', 'forest', 'desert',
        'island', 'coast', 'cliff', 'valley', 'volcano', 'cave',
        'city', 'street', 'architecture', 'building', 'tower',
        'castle', 'temple', 'mosque', 'church', 'monument',
        'museum', 'ruins', 'historic', 'landmark', 'statue',
        'bridge', 'fountain', 'plaza', 'square',
        'hotel', 'resort', 'pool', 'cruise', 'boat', 'harbor',
        'sunset', 'sunrise', 'sky', 'cloud', 'panorama', 'view',
        'safari', 'wildlife', 'jungle', 'tropical', 'palm',
        'snow', 'ski', 'hiking', 'camping', 'adventure',
        'road', 'train', 'airport', 'airplane', 'backpack',
        'map', 'compass', 'culture', 'tradition', 'festival',
    ];

    private const CONFIDENCE_THRESHOLD = 25.0;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $imaggaApiKey,
        private readonly string $imaggaApiSecret,
    ) {}

    /** @return array<string, mixed> */
    public function moderate(string $imagePath): array
    {
        try {
            $uploadResponse = $this->httpClient->request('POST',
                'https://api.imagga.com/v2/uploads',
                [
                    'auth_basic' => [$this->imaggaApiKey, $this->imaggaApiSecret],
                    'body'       => ['image' => fopen($imagePath, 'r')],
                    'timeout'    => 30,
                ]
            );

            $uploadData = $uploadResponse->toArray();
            $uploadId   = isset($uploadData['result']['upload_id']) ? (string) $uploadData['result']['upload_id'] : null;

            if ($uploadId === null) {
                throw new \RuntimeException('Upload Imagga échoué : pas de upload_id');
            }

            $tagsResponse = $this->httpClient->request('GET',
                'https://api.imagga.com/v2/tags',
                [
                    'auth_basic' => [$this->imaggaApiKey, $this->imaggaApiSecret],
                    'query'      => ['image_upload_id' => $uploadId],
                    'timeout'    => 30,
                ]
            );

            $tagsData = $tagsResponse->toArray();
            $tags     = isset($tagsData['result']['tags']) && is_array($tagsData['result']['tags'])
                ? $tagsData['result']['tags']
                : [];

            $tagList = array_map(
                fn(mixed $t): string => is_array($t) ? ((string) ($t['tag']['en'] ?? '')) . '(' . round((float) ($t['confidence'] ?? 0), 1) . ')' : '',
                $tags
            );

            file_put_contents(
                __DIR__ . '/../../var/log/hf_debug.log',
                date('Y-m-d H:i:s') . ' TAGS: ' . implode(', ', array_slice($tagList, 0, 15)) . "\n",
                FILE_APPEND
            );

        } catch (\Throwable $e) {
            file_put_contents(
                __DIR__ . '/../../var/log/hf_debug.log',
                date('Y-m-d H:i:s') . ' ERROR: ' . $e->getMessage() . "\n",
                FILE_APPEND
            );

            return [
                'approved' => true,
                'reason'   => 'Modération indisponible, image acceptée par défaut.',
                'scores'   => [],
            ];
        }

        $matchedTags = [];
        foreach ($tags as $tagItem) {
            if (!is_array($tagItem)) {
                continue;
            }
            $tagName    = strtolower((string) ($tagItem['tag']['en'] ?? ''));
            $confidence = (float) ($tagItem['confidence'] ?? 0);

            if ($confidence >= self::CONFIDENCE_THRESHOLD && in_array($tagName, self::TRAVEL_TAGS, true)) {
                $matchedTags[] = $tagName . ' (' . round($confidence, 1) . '%)';
            }
        }

        $approved = count($matchedTags) > 0;

        return [
            'approved' => $approved,
            'reason'   => $approved
                ? 'Image acceptée — thèmes détectés : ' . implode(', ', array_slice($matchedTags, 0, 3))
                : 'Image refusée : elle ne semble pas liée au voyage ou aux loisirs.',
            'scores'   => $matchedTags,
        ];
    }
}