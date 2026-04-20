<?php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class EventsService
{
    private $httpClient;
    private $apiKey;
    private $cache;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $_ENV['TICKETMASTER_API_KEY'] ?? '';
        $this->cache = new FilesystemAdapter('events', 3600);
    }

    public function getEventsNear(string $city, string $countryCode = 'TN', int $radius = 50): array
    {
        if (empty($this->apiKey)) {
            // Demo fallback
            return [
                ['name' => 'Demo: Carthage Festival', 'date' => '2024-07-15', 'venue' => 'Carthage Amphitheatre'],
                ['name' => 'Demo: Tunis Jazz Night', 'date' => '2024-06-20', 'venue' => 'Cité de la Culture'],
            ];
        }

        return $this->cache->get("events_{$city}_{$countryCode}", function () use ($city, $countryCode, $radius) {
            try {
                $response = $this->httpClient->request('GET', 'https://app.ticketmaster.com/discovery/v2/events.json', [
                    'query' => [
                        'apikey' => $this->apiKey,
                        'city' => $city,
                        'countryCode' => $countryCode,
                        'radius' => $radius,
                        'unit' => 'km',
                        'size' => 10,
                    ],
                    'timeout' => 5,
                ]);
                $data = $response->toArray();
                
                $events = [];
                foreach ($data['_embedded']['events'] ?? [] as $event) {
                    $events[] = [
                        'name' => $event['name'],
                        'date' => $event['dates']['start']['localDate'] ?? null,
                        'venue' => $event['_embedded']['venues'][0]['name'] ?? 'Unknown venue',
                        'url' => $event['url'] ?? null,
                        'image' => $event['images'][0]['url'] ?? null,
                    ];
                }
                return $events;
            } catch (\Exception $e) {
                return [];
            }
        });
    }
}   