<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class ExplorerService
{
    public const CATEGORIES = [
        'cafe'       => ['label' => 'Cafés',       'icon' => 'fa-coffee',            'serp_type' => 'cafe'],
        'restaurant' => ['label' => 'Restaurants', 'icon' => 'fa-utensils',          'serp_type' => 'restaurant'],
        'bar'        => ['label' => 'Bars',         'icon' => 'fa-glass-martini-alt', 'serp_type' => 'bar'],
        'hotel'      => ['label' => 'Hôtels',       'icon' => 'fa-hotel',             'serp_type' => 'hotel'],
    ];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface     $logger,
        private readonly string              $serpApiKey,
    ) {}

    /**
     * @return array<int, array{description: string, place_id: string, lat: float, lon: float}>
     */
    public function autocomplete(string $input): array
    {
        if (mb_strlen(trim($input)) < 2) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET',
                'https://nominatim.openstreetmap.org/search',
                [
                    'query' => [
                        'q'               => $input,
                        'format'          => 'json',
                        'limit'           => 8,
                        'addressdetails'  => 1,
                        'accept-language' => 'fr',
                    ],
                    'headers' => ['User-Agent' => 'DouraMondo/1.0']
                ]
            );

            /** @var array<int, array{place_id: int|string, lat: string, lon: string, address?: array{city?: string, town?: string, village?: string, country?: string}, display_name?: string}> $data */
            $data = $response->toArray();
            if (empty($data)) return [];

            return array_map(fn($place): array => [
                'description' => $this->formatDescription($place),
                'place_id'    => (string) $place['place_id'],
                'lat'         => (float) $place['lat'],
                'lon'         => (float) $place['lon'],
            ], $data);

        } catch (\Throwable $e) {
            $this->logger->error('Autocomplete error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @param array{address?: array{city?: string, town?: string, village?: string, country?: string}, display_name?: string} $place
     */
    private function formatDescription(array $place): string
    {
        $address = $place['address'] ?? [];
        $city = $address['city'] ?? $address['town'] ?? $address['village'] ?? '';
        $country = $address['country'] ?? '';
        
        if ($city && $country) return $city . ', ' . $country;
        return $country ?: ($place['display_name'] ?? '');
    }

    /**
     * @return array<int, array{name: string, address: string, description: string, rating: float|null, reviews_count: mixed, image_url: string|null, lat: mixed, lng: mixed, hours: string|null, website: mixed, phone: mixed, price: string|null, attributes: array<mixed>}>
     */
    public function getTopPlaces(string $location, string $category): array
    {
        if (!array_key_exists($category, self::CATEGORIES)) {
            throw new \InvalidArgumentException('Catégorie invalide');
        }

        $cleanLocation = $this->cleanLocationName($location);

        try {
            $places = $this->searchSerpApi($cleanLocation, $category);
            
            if (empty($places)) {
                throw new \RuntimeException('Aucun lieu trouvé pour cette catégorie');
            }
            
            return $this->normalizePlaces($places);

        } catch (\Throwable $e) {
            $this->logger->error('getTopPlaces error: ' . $e->getMessage());
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * Appel à SerpAPI pour récupérer les lieux
     * 
     * @return array<int, array<string, mixed>>
     */
    private function searchSerpApi(string $location, string $category): array
    {
        $type = self::CATEGORIES[$category]['serp_type'];
        
        $this->logger->info('SerpAPI call', ['location' => $location, 'type' => $type]);
        
        try {
            $response = $this->httpClient->request('GET',
                'https://serpapi.com/search',
                [
                    'query' => [
                        'engine' => 'google_maps',
                        'q' => "best {$type} in {$location}",
                        'api_key' => $this->serpApiKey,
                        'hl' => 'fr',
                    ],
                ]
            );

            /** @var array<string, array<int, array<string, mixed>>> $data */
            $data = $response->toArray();
            
            // 🔴 DEBUG - Sauvegarde dans un fichier pour voir la structure
            file_put_contents(__DIR__ . '/../../var/log/serpapi_debug.json', json_encode($data, JSON_PRETTY_PRINT));
            
            $this->logger->info('SerpAPI response keys', ['keys' => array_keys($data)]);
            
            // Essayer différentes structures possibles
            if (isset($data['place_results']) && !empty($data['place_results'])) {
                $this->logger->info('Found place_results', ['count' => count($data['place_results'])]);
                return $data['place_results'];
            }
            
            if (isset($data['local_results']) && !empty($data['local_results'])) {
                $this->logger->info('Found local_results', ['count' => count($data['local_results'])]);
                return $data['local_results'];
            }
            
            if (isset($data['organic_results']) && !empty($data['organic_results'])) {
                $this->logger->info('Found organic_results', ['count' => count($data['organic_results'])]);
                return $data['organic_results'];
            }
            
            // Si aucune structure trouvée, loguer la réponse complète
            $this->logger->warning('No places found in SerpAPI response', ['response' => json_encode($data)]);
            
            return [];

        } catch (\Throwable $e) {
            $this->logger->error('SerpAPI request error: ' . $e->getMessage());
            throw new \RuntimeException('Erreur lors de l\'appel à SerpAPI: ' . $e->getMessage());
        }
    }

    /**
     * Normalise les données SerpAPI vers notre format interne
     * 
     * @param array<int, array<string, mixed>> $places
     * @return array<int, array{name: string, address: string, description: string, rating: float|null, reviews_count: mixed, image_url: string|null, lat: mixed, lng: mixed, hours: string|null, website: mixed, phone: mixed, price: string|null, attributes: array<mixed>}>
     */
    private function normalizePlaces(array $places): array
    {
        $normalized = [];
        
        foreach (array_slice($places, 0, 3) as $place) {
            // Extraction des coordonnées
            $lat = null;
            $lng = null;
            
            if (isset($place['gps_coordinates'])) {
                $lat = $place['gps_coordinates']['latitude'] ?? null;
                $lng = $place['gps_coordinates']['longitude'] ?? null;
            }
            
            // Fallback sur d'autres structures possibles
            if (!$lat && isset($place['latitude'])) {
                $lat = $place['latitude'];
                $lng = $place['longitude'];
            }
            
            // Extraction du prix
            $price = $place['price'] ?? null;
            if ($price && is_numeric($price)) {
                $price = str_repeat('$', min(4, (int)$price));
            }
            
            // Extraction des horaires
            $hours = null;
            if (isset($place['hours'])) {
                $hours = is_array($place['hours']) 
                    ? implode(' • ', $place['hours']) 
                    : (string) $place['hours'];
            }
            
            // Extraction de la photo
            $imageUrl = null;
            if (isset($place['thumbnail'])) {
                $imageUrl = (string) $place['thumbnail'];
            } elseif (isset($place['images'][0])) {
                $imageUrl = (string) $place['images'][0];
            }
            
            $normalized[] = [
                'name' => (string) ($place['title'] ?? $place['name'] ?? 'Établissement'),
                'address' => (string) ($place['address'] ?? ''),
                'description' => (string) ($place['description'] ?? $place['snippet'] ?? ''),
                'rating' => isset($place['rating']) ? (float) $place['rating'] : null,
                'reviews_count' => $place['reviews'] ?? null,
                'image_url' => $imageUrl,
                'lat' => $lat,
                'lng' => $lng,
                'hours' => $hours,
                'website' => $place['website'] ?? null,
                'phone' => $place['phone'] ?? null,
                'price' => $price,
                'attributes' => $place['features'] ?? [],
            ];
        }
        
        return $normalized;
    }

    private function cleanLocationName(string $location): string
    {
        $parts = explode(',', $location);
        return trim($parts[0]);
    }

    /**
     * Méthode pour les détails d'un lieu (optionnel)
     * 
     * @return array<string, mixed>
     */
    public function getPlaceDetails(string $placeId): array
    {
        // À implémenter si besoin avec SerpAPI
        return [];
    }
}