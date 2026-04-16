<?php

namespace App\Controller;

use App\Service\TravelpayoutsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FlightController extends AbstractController
{
    #[Route('/api/flights/search', name: 'api_flights_search', methods: ['GET'])]
    public function search(Request $request, TravelpayoutsService $travelpayouts): JsonResponse
    {
        $dep = strtoupper(trim($request->query->get('dep', '')));
        $arr = strtoupper(trim($request->query->get('arr', '')));
        $date = $request->query->get('date', date('Y-m-d'));
        
        if (strlen($dep) !== 3 || strlen($arr) !== 3) {
            return $this->json(['error' => 'Codes IATA invalides (3 lettres requis)'], 400);
        }
        
        // Appel à l'API Travelpayouts avec la date
        $result = $travelpayouts->getDirectFlights($dep, $arr, $date);
        
        if ($result['error']) {
            // En cas d'erreur, retourner des données mockées
            return $this->json([
                'count' => 2,
                'flights' => $this->getMockFlights($dep, $arr, $date),
                'warning' => 'API Travelpayouts: ' . $result['error']
            ]);
        }
        
        // Transformer les données pour votre frontend
        $flights = [];
        if (!empty($result['flights'])) {
            foreach ($result['flights'] as $flightData) {
                // Formatage des dates
                $departureDate = $flightData['departure_at'] ?? $date . ' 00:00:00';
                $returnDate = $flightData['return_at'] ?? $date . ' 00:00:00';
                
                $flights[] = [
                    'airline' => $this->getAirlineName($flightData['airline'] ?? 'N/A'),
                    'flight_number' => $flightData['flight_number'] ?? 'Vol direct',
                    'departure' => $dep,
                    'dep_time' => $departureDate,
                    'arrival' => $arr,
                    'arr_time' => $returnDate,
                    'price' => $flightData['price'] ?? 0,
                    'currency' => $flightData['currency'] ?? 'EUR',
                    'gate' => 'N/A',
                    'terminal' => 'N/A',
                    'status' => 'scheduled'
                ];
            }
        }
        
        // Si aucun vol trouvé, utiliser les données mockées
        if (empty($flights)) {
            $flights = $this->getMockFlights($dep, $arr, $date);
        }
        
        return $this->json([
            'count' => count($flights),
            'flights' => $flights
        ]);
    }
    
    private function getMockFlights(string $dep, string $arr, string $date): array
    {
        $mockFlights = [
            'TUN-CDG' => [
                ['airline' => 'Tunisair', 'flight_number' => 'TU726', 'departure' => 'Tunis-Carthage (TUN)', 'dep_time' => $date . ' 08:30:00', 'arrival' => 'Paris Charles de Gaulle (CDG)', 'arr_time' => $date . ' 11:45:00', 'price' => 180, 'currency' => 'EUR', 'status' => 'scheduled'],
                ['airline' => 'Air France', 'flight_number' => 'AF1085', 'departure' => 'Tunis-Carthage (TUN)', 'dep_time' => $date . ' 14:20:00', 'arrival' => 'Paris Charles de Gaulle (CDG)', 'arr_time' => $date . ' 17:35:00', 'price' => 210, 'currency' => 'EUR', 'status' => 'scheduled'],
                ['airline' => 'Nouvelair', 'flight_number' => 'BJ510', 'departure' => 'Tunis-Carthage (TUN)', 'dep_time' => $date . ' 19:00:00', 'arrival' => 'Paris Charles de Gaulle (CDG)', 'arr_time' => $date . ' 22:15:00', 'price' => 165, 'currency' => 'EUR', 'status' => 'scheduled'],
            ],
            'CDG-TUN' => [
                ['airline' => 'Air France', 'flight_number' => 'AF1084', 'departure' => 'Paris Charles de Gaulle (CDG)', 'dep_time' => $date . ' 10:30:00', 'arrival' => 'Tunis-Carthage (TUN)', 'arr_time' => $date . ' 13:45:00', 'price' => 195, 'currency' => 'EUR', 'status' => 'scheduled'],
            ],
            'LYS-MRS' => [
                ['airline' => 'Air France', 'flight_number' => 'AF9402', 'departure' => 'Lyon-Saint Exupéry (LYS)', 'dep_time' => $date . ' 09:00:00', 'arrival' => 'Marseille Provence (MRS)', 'arr_time' => $date . ' 09:55:00', 'price' => 85, 'currency' => 'EUR', 'status' => 'scheduled'],
            ],
            'MRS-LYS' => [
                ['airline' => 'Air France', 'flight_number' => 'AF9403', 'departure' => 'Marseille Provence (MRS)', 'dep_time' => $date . ' 10:30:00', 'arrival' => 'Lyon-Saint Exupéry (LYS)', 'arr_time' => $date . ' 11:25:00', 'price' => 85, 'currency' => 'EUR', 'status' => 'scheduled'],
            ],
            'CDG-LYS' => [
                ['airline' => 'Air France', 'flight_number' => '7362', 'departure' => 'Paris Charles de Gaulle (CDG)', 'dep_time' => $date . ' 07:25:00', 'arrival' => 'Lyon-Saint Exupéry (LYS)', 'arr_time' => $date . ' 13:40:00', 'price' => 89, 'currency' => 'EUR', 'status' => 'scheduled'],
                ['airline' => 'Lufthansa', 'flight_number' => '2227', 'departure' => 'Paris Charles de Gaulle (CDG)', 'dep_time' => $date . ' 09:10:00', 'arrival' => 'Lyon-Saint Exupéry (LYS)', 'arr_time' => $date . ' 12:50:00', 'price' => 95, 'currency' => 'EUR', 'status' => 'scheduled'],
            ],
        ];
        
        $key = $dep . '-' . $arr;
        
        if (isset($mockFlights[$key])) {
            return $mockFlights[$key];
        }
        
        // Mock générique
        return [
            [
                'airline' => 'Vol Direct',
                'flight_number' => 'FL' . rand(100, 999),
                'departure' => $dep,
                'dep_time' => $date . ' 09:00:00',
                'arrival' => $arr,
                'arr_time' => $date . ' 11:00:00',
                'price' => rand(100, 300),
                'currency' => 'EUR',
                'status' => 'scheduled'
            ]
        ];
    }
    
    private function getAirlineName(string $code): string
    {
        $airlines = [
            'AF' => 'Air France',
            'TU' => 'Tunisair',
            'BJ' => 'Nouvelair',
            'BA' => 'British Airways',
            'LH' => 'Lufthansa',
            'TK' => 'Turkish Airlines',
            'QR' => 'Qatar Airways',
            'EK' => 'Emirates',
        ];
        
        return $airlines[$code] ?? $code;
    }
}