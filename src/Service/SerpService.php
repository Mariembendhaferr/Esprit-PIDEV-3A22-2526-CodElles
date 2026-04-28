<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SerpApiService
{
    private string $apiKey;
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client, string $serpApiKey)
    {
        $this->client = $client;
        $this->apiKey = $serpApiKey;
    }

    /**
     * Recherche des vols sur Google Flights via SerpApi
     */
    public function searchFlights(
        string $departureId,
        string $arrivalId,
        string $outboundDate,
        ?string $returnDate = null,
        string $currency = 'EUR',
        string $hl = 'fr'
    ): array {
        if (empty($this->apiKey)) {
            return [
                'error' => 'Clé API SerpApi manquante',
                'best_flights' => [],
                'other_flights' => [],
                'flights' => [],
                'search_metadata' => []
            ];
        }

        try {
            $query = [
                'engine'         => 'google_flights',
                'departure_id'   => strtoupper($departureId),
                'arrival_id'     => strtoupper($arrivalId),
                'outbound_date'  => $outboundDate,
                'currency'       => strtoupper($currency),
                'hl'             => $hl,
                'api_key'        => $this->apiKey,
            ];

            if ($returnDate) {
                $query['type']        = '1'; // round-trip
                $query['return_date'] = $returnDate;
            } else {
                $query['type'] = '2'; // one-way
            }

            $response = $this->client->request('GET', 'https://serpapi.com/search.json', [
                'query' => $query,
            ]);

            $data = $response->toArray();

            if (isset($data['error'])) {
                return [
                    'error'          => $data['error'],
                    'best_flights'   => [],
                    'other_flights'  => [],
                    'flights'        => [],
                    'search_metadata'=> []
                ];
            }

            $bestFlights  = $data['best_flights']  ?? [];
            $otherFlights = $data['other_flights'] ?? [];

            return [
                'error'           => null,
                'best_flights'    => $bestFlights,
                'other_flights'   => $otherFlights,
                'flights'         => array_merge($bestFlights, $otherFlights), // ← clé unifiée
                'search_metadata' => $data['search_metadata'] ?? [],
            ];

        } catch (\Exception $e) {
            return [
                'error'          => $e->getMessage(),
                'best_flights'   => [],
                'other_flights'  => [],
                'flights'        => [],
                'search_metadata'=> []
            ];
        }
    }

    /**
     * Version simplifiée pour le frontend — garde la structure complète des vols
     */
    public function getSimpleFlights(
        string $departureId,
        string $arrivalId,
        string $outboundDate,
        ?string $returnDate = null,
        string $currency = 'EUR'
    ): array {
        $result = $this->searchFlights($departureId, $arrivalId, $outboundDate, $returnDate, $currency);

        if ($result['error']) {
            return ['error' => $result['error'], 'count' => 0, 'flights' => []];
        }

        $simpleFlights = [];

        foreach ($result['best_flights'] as $flightOption) {
            $formatted = $this->formatFlightForFrontend($flightOption, 'best');
            if (!empty($formatted)) {
                $simpleFlights[] = $formatted;
            }
        }

        foreach ($result['other_flights'] as $flightOption) {
            $formatted = $this->formatFlightForFrontend($flightOption, 'other');
            if (!empty($formatted)) {
                $simpleFlights[] = $formatted;
            }
        }

        return [
            'error'   => null,
            'count'   => count($simpleFlights),
            'flights' => $simpleFlights,
        ];
    }

    /**
     * Formate un vol complet (avec potentiellement plusieurs segments) pour le frontend
     */
    private function formatFlightForFrontend(array $flightOption, string $type = 'best'): array
    {
        $segments = $flightOption['flights'] ?? [];

        if (empty($segments)) {
            return [];
        }

        $firstSegment = $segments[0];
        $lastSegment  = $segments[count($segments) - 1];

        $departureAirport = $firstSegment['departure_airport'] ?? [];
        $arrivalAirport   = $lastSegment['arrival_airport']   ?? [];

        // Escales
        $layovers = [];
        foreach ($flightOption['layovers'] ?? [] as $layover) {
            $layovers[] = [
                'airport'          => $layover['name'] ?? $layover['id'] ?? 'N/A',
                'airport_code'     => $layover['id']   ?? 'N/A',
                'duration_minutes' => $layover['duration'] ?? 0,
                'duration'         => $this->formatDuration($layover['duration'] ?? 0),
                'overnight'        => $layover['overnight'] ?? false,
            ];
        }

        // Segments détaillés
        $segmentsDetails = [];
        foreach ($segments as $segment) {
            $segmentsDetails[] = [
                'airline'        => $segment['airline']       ?? 'N/A',
                'airline_logo'   => $segment['airline_logo']  ?? null,
                'flight_number'  => $segment['flight_number'] ?? 'N/A',
                'departure'      => $segment['departure_airport']['name'] ?? $segment['departure_airport']['id'] ?? 'N/A',
                'departure_code' => $segment['departure_airport']['id']   ?? 'N/A',
                'dep_time'       => $segment['departure_airport']['time'] ?? 'N/A',
                'arrival'        => $segment['arrival_airport']['name']   ?? $segment['arrival_airport']['id'] ?? 'N/A',
                'arrival_code'   => $segment['arrival_airport']['id']     ?? 'N/A',
                'arr_time'       => $segment['arrival_airport']['time']   ?? 'N/A',
                'duration_minutes'=> $segment['duration'] ?? 0,
                'duration'       => $this->formatDuration($segment['duration'] ?? 0),
                'airplane'       => $segment['airplane']  ?? 'N/A',
                'travel_class'   => $segment['travel_class'] ?? 'Economy',
                'legroom'        => $segment['legroom']   ?? null,
                'extensions'     => $segment['extensions'] ?? [],
                'overnight'      => $segment['overnight'] ?? false,
            ];
        }

        $hasLayovers = count($segments) > 1;
        $airlineName = $hasLayovers ? 'Vol avec escale(s)' : ($segments[0]['airline'] ?? 'N/A');
        $airlineLogo = $hasLayovers ? ($flightOption['airline_logo'] ?? null) : ($segments[0]['airline_logo'] ?? null);

        return [
            'id'                    => uniqid('flight_'),
            'airline'               => $airlineName,
            'airline_logo'          => $airlineLogo,
            'flight_number'         => $segments[0]['flight_number'] ?? 'N/A',
            'price'                 => $flightOption['price'] ?? 0,
            'currency'              => 'EUR',
            'type'                  => $flightOption['type'] ?? ($type === 'best' ? 'Best flight' : 'Other flight'),

            'departure'             => $departureAirport['id']   ?? 'N/A',
            'departure_name'        => $departureAirport['name'] ?? 'N/A',
            'dep_time'              => $this->formatDateTime($departureAirport['time'] ?? null),
            'arrival'               => $arrivalAirport['id']     ?? 'N/A',
            'arrival_name'          => $arrivalAirport['name']   ?? 'N/A',
            'arr_time'              => $this->formatDateTime($arrivalAirport['time'] ?? null),

            'total_duration_minutes'=> $flightOption['total_duration'] ?? 0,
            'duration'              => $this->formatDuration($flightOption['total_duration'] ?? 0),

            'has_layovers'          => $hasLayovers,
            'layovers_count'        => count($layovers),
            'layovers'              => $layovers,

            'segments'              => $segmentsDetails,

            'carbon_emissions'      => $flightOption['carbon_emissions'] ?? null,

            'departure_token'       => $flightOption['departure_token'] ?? null,
            'booking_token'         => $flightOption['booking_token']   ?? null,

            'status'                => 'scheduled',
            'gate'                  => 'N/A',
            'terminal'              => 'N/A',
        ];
    }

    /**
     * Formate la durée (minutes → "2h30")
     */
    private function formatDuration(int $minutes): string
    {
        if ($minutes <= 0) {
            return 'N/A';
        }
        $hours = (int) floor($minutes / 60);
        $mins  = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return $hours . 'h' . str_pad((string) $mins, 2, '0', STR_PAD_LEFT);
        } elseif ($hours > 0) {
            return $hours . 'h';
        }
        return $mins . 'min';
    }

    /**
     * Normalise une date/heure SerpApi ("2023-10-03 15:10" → "2023-10-03 15:10:00")
     */
    private function formatDateTime(?string $datetime): string
    {
        if (!$datetime) {
            return date('Y-m-d H:i:s');
        }
        if (strlen($datetime) === 16) {
            return $datetime . ':00';
        }
        return $datetime;
    }

    /**
     * Récupère UNIQUEMENT les vols directs (sans escale)
     */
    public function getDirectFlightsOnly(
        string $departureId,
        string $arrivalId,
        string $outboundDate,
        ?string $returnDate = null,
        string $currency = 'EUR'
    ): array {
        $result = $this->getSimpleFlights($departureId, $arrivalId, $outboundDate, $returnDate, $currency);

        if ($result['error']) {
            return $result;
        }

        $directFlights = array_values(array_filter(
            $result['flights'],
            fn($flight) => !$flight['has_layovers']
        ));

        return [
            'error'   => null,
            'count'   => count($directFlights),
            'flights' => $directFlights,
        ];
    }
}