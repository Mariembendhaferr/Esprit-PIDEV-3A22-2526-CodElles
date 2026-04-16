<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FlightService
{
    private string $apiKey;
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client, string $aviationstackKey)
    {
        $this->client = $client;
        $this->apiKey = $aviationstackKey;
    }

    /**
     * Recherche des vols réels entre deux villes à une date donnée
     * @param string $depIata  Code IATA départ (ex: "TUN")
     * @param string $arrIata  Code IATA arrivée (ex: "CDG")
     * @param string $date     Format YYYY-MM-DD
     */
  public function searchFlights(string $depIata, string $arrIata, string $date): array
{
    $query = [
        'access_key'    => $this->apiKey,
        'dep_iata'      => $depIata,
        'arr_iata'      => $arrIata,
        'flight_status' => 'scheduled',
        'limit'         => 10,
    ];

    // On enlève la date pour tester
    // $query['flight_date'] = $date;   // ← commente cette ligne

    $response = $this->client->request('GET', 'http://api.aviationstack.com/v1/flights', [
        'query' => $query
    ]);

    $data = $response->toArray(false);

    if (!isset($data['data']) || empty($data['data'])) {
        // Optionnel : log pour voir ce que renvoie vraiment l'API
        // dump($data); 
        return [];
    }

        return array_map(function ($flight) {
            return [
                'airline'       => $flight['airline']['name'] ?? 'N/A',
                'flight_number' => $flight['flight']['iata'] ?? 'N/A',
                'departure'     => $flight['departure']['airport'] ?? 'N/A',
                'dep_time'      => $flight['departure']['scheduled'] ?? 'N/A',
                'arrival'       => $flight['arrival']['airport'] ?? 'N/A',
                'arr_time'      => $flight['arrival']['scheduled'] ?? 'N/A',
                'status'        => $flight['flight_status'] ?? 'N/A',
            ];
        }, $data['data']);
    }
}