<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TravelpayoutsService
{
    private string $apiKey;
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client, string $travelpayoutsApiKey)
    {
        $this->client = $client;
        $this->apiKey = $travelpayoutsApiKey;
    }

    /**
     * Récupère les prix mensuels pour une route
     */
    public function getMonthlyPrices(string $origin, string $destination, string $currency = 'EUR'): array
    {
        if (empty($this->apiKey)) {
            return ['error' => 'Clé API Travelpayouts manquante', 'flights' => []];
        }

        try {
            $response = $this->client->request('GET', 'https://api.travelpayouts.com/v1/prices/monthly', [
                'query' => [
                    'currency' => $currency,
                    'origin' => $origin,
                    'destination' => $destination,
                    'token' => $this->apiKey
                ]
            ]);

            $data = $response->toArray();
            
            if (isset($data['error'])) {
                return ['error' => $data['error'], 'flights' => []];
            }
            
            return ['error' => null, 'flights' => $data['data'] ?? []];
            
        } catch (\Exception $e) {
            return ['error' => $e->getMessage(), 'flights' => []];
        }
    }

    /**
     * Récupère les vols directs pour une date spécifique
     */
    public function getDirectFlights(string $origin, string $destination, string $date, string $currency = 'EUR'): array
    {
        if (empty($this->apiKey)) {
            return ['error' => 'Clé API Travelpayouts manquante', 'flights' => []];
        }

        try {
            $response = $this->client->request('GET', 'https://api.travelpayouts.com/v1/prices/direct', [
                'query' => [
                    'currency' => $currency,
                    'origin' => $origin,
                    'destination' => $destination,
                    'depart_date' => $date,
                    'token' => $this->apiKey
                ]
            ]);

            $data = $response->toArray();
            
            if (isset($data['error'])) {
                return ['error' => $data['error'], 'flights' => []];
            }
            
            return ['error' => null, 'flights' => $data['data'] ?? []];
            
        } catch (\Exception $e) {
            return ['error' => $e->getMessage(), 'flights' => []];
        }
    }

    /**
     * Récupère les meilleures offres pour une destination
     */
    public function getCheapestPrices(string $origin, string $destination, string $currency = 'EUR'): array
    {
        try {
            $response = $this->client->request('GET', 'https://api.travelpayouts.com/v1/prices/cheap', [
                'query' => [
                    'currency' => $currency,
                    'origin' => $origin,
                    'destination' => $destination,
                    'token' => $this->apiKey
                ]
            ]);

            return $response->toArray();
            
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Récupère la liste des compagnies aériennes
     */
    public function getAirlines(): array
    {
        try {
            $response = $this->client->request('GET', 'https://api.travelpayouts.com/v1/reference/airlines', [
                'query' => ['token' => $this->apiKey]
            ]);

            return $response->toArray();
            
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}