<?php
// src/Service/WeatherService.php
namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class WeatherService
{
    private $httpClient;
    private $apiKey;
    private $cache;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
        // Get free API key at https://openweathermap.org/api
        $this->apiKey = $_ENV['WEATHER_API_KEY'] ?? '';
        $this->cache = new FilesystemAdapter('weather', 3600); // Cache 1 hour
    }

    /**
     * Get weather for a city
     * @param string $city City name (e.g., 'Tunis')
     * @param string $lang Language code (e.g., 'fr')
     */
    public function getWeather(string $city, string $lang = 'fr'): ?array
    {
        // Demo mode: return mock data if no API key
        if (empty($this->apiKey)) {
            return [
                'temp' => rand(15, 30),
                'feels_like' => rand(14, 29),
                'description' => $lang === 'fr' ? 'Ensoleillé' : 'Sunny',
                'icon' => '01d',
                'humidity' => rand(40, 80),
                'city' => $city,
                'demo' => true,
            ];
        }

        return $this->cache->get("weather_{$city}_{$lang}", function () use ($city, $lang) {
            try {
                $response = $this->httpClient->request('GET', 'https://api.openweathermap.org/data/2.5/weather', [
                    'query' => [
                        'q' => $city,
                        'appid' => $this->apiKey,
                        'units' => 'metric',
                        'lang' => $lang,
                    ],
                    'timeout' => 5,
                ]);
                $data = $response->toArray();
                
                return [
                    'temp' => round($data['main']['temp']),
                    'feels_like' => round($data['main']['feels_like']),
                    'description' => ucfirst($data['weather'][0]['description']),
                    'icon' => $data['weather'][0]['icon'],
                    'humidity' => $data['main']['humidity'],
                    'city' => $data['name'],
                    'demo' => false,
                ];
            } catch (\Exception $e) {
                return null;
            }
        });
    }
}