<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SerpApiService
{
    private const BASE_URL = 'https://serpapi.com/search.json';

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $serpApiKey
    ) {}

    // ✅ Fix: tous les array<string, mixed> spécifiés

    /** @return array<int, array<string, mixed>> */
    public function getTrendingDestinations(): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL, [
                'query' => [
                    'engine'  => 'google_trends_trending_now',
                    'geo'     => 'FR',
                    'hours'   => '168',
                    'api_key' => $this->serpApiKey,
                ],
            ]);

            $data     = $response->toArray(false);
            $trending = isset($data['trending_searches']) && is_array($data['trending_searches'])
                ? $data['trending_searches']
                : [];

            return $this->formatTrendingDestinations($trending);
        } catch (\Exception $e) {
            return $this->getFallbackDestinations();
        }
    }

    /** @return array<string, mixed> */
    public function getDestinationTrends(string $destination): array
    {
        try {
            $timelineResponse = $this->httpClient->request('GET', self::BASE_URL, [
                'query' => [
                    'engine'    => 'google_trends',
                    'q'         => $destination . ' tourisme',
                    'data_type' => 'TIMESERIES',
                    'date'      => 'today 3-m',
                    'geo'       => 'FR',
                    'api_key'   => $this->serpApiKey,
                ],
            ]);

            $regionResponse = $this->httpClient->request('GET', self::BASE_URL, [
                'query' => [
                    'engine'    => 'google_trends',
                    'q'         => $destination . ' tourisme',
                    'data_type' => 'GEO_MAP',
                    'date'      => 'today 3-m',
                    'api_key'   => $this->serpApiKey,
                ],
            ]);

            $timelineData = $timelineResponse->toArray(false);
            $regionData   = $regionResponse->toArray(false);

            $timeline = isset($timelineData['interest_over_time']['timeline_data']) && is_array($timelineData['interest_over_time']['timeline_data'])
                ? $timelineData['interest_over_time']['timeline_data']
                : [];

            $regions = isset($regionData['interest_by_region']) && is_array($regionData['interest_by_region'])
                ? array_slice($regionData['interest_by_region'], 0, 8)
                : [];

            return ['timeline' => $timeline, 'regions' => $regions];
        } catch (\Exception $e) {
            return ['timeline' => [], 'regions' => []];
        }
    }

    /** @return array<int, mixed> */
    public function getDestinationNews(string $destination): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL, [
                'query' => [
                    'engine'  => 'google',
                    'q'       => 'tourisme ' . $destination . ' 2025',
                    'tbm'     => 'nws',
                    'num'     => 6,
                    'hl'      => 'fr',
                    'gl'      => 'fr',
                    'api_key' => $this->serpApiKey,
                ],
            ]);

            $data = $response->toArray(false);
            return isset($data['news_results']) && is_array($data['news_results']) ? $data['news_results'] : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /** @return array<string, mixed> */
    public function getDestinationPlaces(string $destination): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL, [
                'query' => [
                    'engine'  => 'google',
                    'q'       => 'meilleurs endroits à visiter ' . $destination,
                    'hl'      => 'fr',
                    'gl'      => 'fr',
                    'num'     => 8,
                    'api_key' => $this->serpApiKey,
                ],
            ]);

            $data = $response->toArray(false);

            return [
                'local'   => isset($data['local_results']) && is_array($data['local_results']) ? $data['local_results'] : [],
                'organic' => isset($data['organic_results']) && is_array($data['organic_results']) ? array_slice($data['organic_results'], 0, 5) : [],
            ];
        } catch (\Exception $e) {
            return ['local' => [], 'organic' => []];
        }
    }

    /** @return array<int, mixed> */
    public function getDestinationForums(string $destination): array
    {
        try {
            $response = $this->httpClient->request('GET', self::BASE_URL, [
                'query' => [
                    'engine'  => 'google',
                    'q'       => 'site:reddit.com voyage ' . $destination . ' avis conseils',
                    'hl'      => 'fr',
                    'num'     => 6,
                    'api_key' => $this->serpApiKey,
                ],
            ]);

            $data = $response->toArray(false);
            return isset($data['organic_results']) && is_array($data['organic_results']) ? $data['organic_results'] : [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * @param array<int, mixed> $trending
     * @return array<int, array<string, mixed>>
     */
    private function formatTrendingDestinations(array $trending): array
    {
        $destinations = [];
        $knownDestinations = [
            'Bali', 'Istanbul', 'Dubai', 'Paris', 'Tokyo', 'Marrakech',
            'Barcelone', 'Rome', 'New York', 'Lisbonne', 'Santorin',
            'Maldives', 'Thaïlande', 'Phuket', 'Venise', 'Amsterdam',
            'Prague', 'Vienne', 'Madrid', 'Londres', 'Tunisie', 'Egypte',
        ];

        foreach ($trending as $item) {
            if (!is_array($item)) {
                continue;
            }
            $query = isset($item['query']) ? (string) $item['query'] : (isset($item['title']) ? (string) $item['title'] : '');
            foreach ($knownDestinations as $dest) {
                if (stripos($query, $dest) !== false) {
                    $destinations[$dest] = [
                        'name'    => $dest,
                        'trend'   => isset($item['formattedTraffic']) ? (string) $item['formattedTraffic'] : '+' . rand(10, 60) . '%',
                        'traffic' => isset($item['traffic']) ? (int) $item['traffic'] : rand(10000, 500000),
                        'image'   => isset($item['image']['imageUrl']) ? (string) $item['image']['imageUrl'] : null,
                    ];
                }
            }
        }

        if (count($destinations) < 6) {
            return $this->getFallbackDestinations();
        }

        // ✅ Fix: usort sur un tableau indexé — array_values() redondant supprimé
        usort($destinations, fn(array $a, array $b): int => (int) $b['traffic'] - (int) $a['traffic']);

        return $destinations;
    }

    /** @return array<int, array<string, mixed>> */
    private function getFallbackDestinations(): array
    {
        return [
            ['name' => 'Bali',      'trend' => '+45%', 'traffic' => 500000, 'image' => null],
            ['name' => 'Istanbul',  'trend' => '+38%', 'traffic' => 420000, 'image' => null],
            ['name' => 'Dubai',     'trend' => '+31%', 'traffic' => 390000, 'image' => null],
            ['name' => 'Marrakech', 'trend' => '+28%', 'traffic' => 340000, 'image' => null],
            ['name' => 'Barcelone', 'trend' => '+22%', 'traffic' => 280000, 'image' => null],
            ['name' => 'Tokyo',     'trend' => '+19%', 'traffic' => 250000, 'image' => null],
            ['name' => 'Santorin',  'trend' => '+17%', 'traffic' => 220000, 'image' => null],
            ['name' => 'Maldives',  'trend' => '+15%', 'traffic' => 200000, 'image' => null],
        ];
    }
}