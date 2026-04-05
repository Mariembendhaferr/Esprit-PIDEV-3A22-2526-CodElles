<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class PexelsService
{
    public function __construct(
        private HttpClientInterface $client,
        private string $apiKey
    ) {}

    public function searchImage(string $query): ?string
    {
        try {
            $response = $this->client->request('GET',
                'https://api.pexels.com/v1/search', [
                    'headers' => ['Authorization' => $this->apiKey],
                    'query'   => ['query' => $query, 'per_page' => 1, 'orientation' => 'landscape'],
                ]
            );

            $data = $response->toArray();

            if (!empty($data['photos'])) {
                return $data['photos'][0]['src']['large'];
            }
        } catch (\Exception $e) {}

        return null;
    }
}