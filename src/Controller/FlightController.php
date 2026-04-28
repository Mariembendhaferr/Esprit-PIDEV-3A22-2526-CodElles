<?php

namespace App\Controller;

use App\Service\SerpService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class FlightController extends AbstractController
{
    /**
     * GET /api/flights/search
     *
     * Paramètres :
     *   dep          : code IATA départ  (ex: TUN)
     *   arr          : code IATA arrivée (ex: CDG)
     *   date         : YYYY-MM-DD        (défaut : aujourd'hui)
     *   return_date  : YYYY-MM-DD        (optionnel — aller-retour)
     *   currency     : devise            (défaut : EUR)
     */
    #[Route('/api/flights/search', name: 'api_flights_search', methods: ['GET'])]
    public function search(Request $request, SerpService $serpApi): JsonResponse
    {
        [$dep, $arr, $date, $returnDate, $currency, $error] = $this->extractAndValidateParams($request);

        if ($error) {
            return $this->json(['error' => $error], 400);
        }

        $result = $serpApi->getSimpleFlights($dep, $arr, $date, $returnDate, $currency);

        // ── Erreur API → mock ──────────────────────────────────────────────
        if ($result['error']) {
            return $this->json([
                'count'   => 2,
                'flights' => $this->getMockFlights($dep, $arr, $date),
                'source'  => 'MOCK_DATA',
                'warning' => 'API SerpApi : ' . $result['error'],
            ]);
        }

        // ── Aucun vol trouvé → mock ────────────────────────────────────────
        // FIX : on vérifie 'count' et non 'flights' (qui était toujours vide avant)
        if (empty($result['count']) || $result['count'] === 0) {
            $flights = $this->getMockFlights($dep, $arr, $date);
            return $this->json([
                'count'   => count($flights),
                'flights' => $flights,
                'source'  => 'MOCK_DATA_NO_FLIGHTS',
            ]);
        }

        // ── Données réelles SerpApi ────────────────────────────────────────
        return $this->json([
            'count'   => $result['count'],
            'flights' => $result['flights'],
            'source'  => 'SERPAPI_REAL_DATA',
        ]);
    }

    /**
     * GET /api/flights/search/detailed
     *
     * Retourne les tableaux bruts best_flights / other_flights de SerpApi.
     */
    #[Route('/api/flights/search/detailed', name: 'api_flights_search_detailed', methods: ['GET'])]
    public function searchDetailed(Request $request, SerpService $serpApi): JsonResponse
    {
        [$dep, $arr, $date, $returnDate, $currency, $error] = $this->extractAndValidateParams($request);

        if ($error) {
            return $this->json(['error' => $error], 400);
        }

        $result = $serpApi->searchFlights($dep, $arr, $date, $returnDate, $currency);

        if ($result['error']) {
            return $this->json([
                'error'   => $result['error'],
                'flights' => $this->getMockFlights($dep, $arr, $date),
                'source'  => 'MOCK_DATA',
            ]);
        }

        return $this->json([
            'best_flights'    => $result['best_flights'],
            'other_flights'   => $result['other_flights'],
            'count'           => count($result['flights']),
            'source'          => 'SERPAPI_REAL_DATA',
            'search_metadata' => $result['search_metadata'],
        ]);
    }

    /**
     * GET /api/flights/search/direct
     *
     * Retourne uniquement les vols sans escale.
     */
    #[Route('/api/flights/search/direct', name: 'api_flights_search_direct', methods: ['GET'])]
    public function searchDirect(Request $request, SerpService $serpApi): JsonResponse
    {
        [$dep, $arr, $date, $returnDate, $currency, $error] = $this->extractAndValidateParams($request);

        if ($error) {
            return $this->json(['error' => $error], 400);
        }

        $result = $serpApi->getDirectFlightsOnly($dep, $arr, $date, $returnDate, $currency);

        if ($result['error']) {
            return $this->json([
                'error'   => $result['error'],
                'flights' => $this->getMockFlights($dep, $arr, $date),
                'source'  => 'MOCK_DATA',
            ]);
        }

        return $this->json([
            'count'   => $result['count'],
            'flights' => $result['flights'],
            'source'  => 'SERPAPI_REAL_DATA_DIRECT_ONLY',
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    //  Helpers privés
    // ──────────────────────────────────────────────────────────────────────────

    /**
     * Extrait et valide les paramètres communs aux trois routes.
     *
     * @return array [dep, arr, date, returnDate, currency, errorMessage|null]
     */
    private function extractAndValidateParams(Request $request): array
    {
        $dep        = strtoupper(trim($request->query->get('dep', '')));
        $arr        = strtoupper(trim($request->query->get('arr', '')));
        $date       = $request->query->get('date', date('Y-m-d'));
        $returnDate = $request->query->get('return_date');
        $currency   = strtoupper($request->query->get('currency', 'EUR'));

        if (strlen($dep) !== 3 || strlen($arr) !== 3) {
            return [$dep, $arr, $date, $returnDate, $currency, 'Codes IATA invalides (3 lettres requis)'];
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return [$dep, $arr, $date, $returnDate, $currency, 'Format de date invalide (YYYY-MM-DD requis)'];
        }

        if ($returnDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $returnDate)) {
            return [$dep, $arr, $date, $returnDate, $currency, 'Format de return_date invalide (YYYY-MM-DD requis)'];
        }

        return [$dep, $arr, $date, $returnDate, $currency, null];
    }

    /**
     * Données mockées utilisées en fallback.
     */
    private function getMockFlights(string $dep, string $arr, string $date): array
    {
        $mockFlights = [
            'TUN-CDG' => [
                ['airline' => 'Tunisair',   'flight_number' => 'TU726',  'departure' => 'TUN', 'dep_time' => $date . ' 08:30:00', 'arrival' => 'CDG', 'arr_time' => $date . ' 11:45:00', 'price' => 180, 'currency' => 'EUR', 'duration' => '3h15', 'travel_class' => 'Economy', 'status' => 'scheduled'],
                ['airline' => 'Air France', 'flight_number' => 'AF1085', 'departure' => 'TUN', 'dep_time' => $date . ' 14:20:00', 'arrival' => 'CDG', 'arr_time' => $date . ' 17:35:00', 'price' => 210, 'currency' => 'EUR', 'duration' => '3h15', 'travel_class' => 'Economy', 'status' => 'scheduled'],
                ['airline' => 'Nouvelair',  'flight_number' => 'BJ510',  'departure' => 'TUN', 'dep_time' => $date . ' 19:00:00', 'arrival' => 'CDG', 'arr_time' => $date . ' 22:15:00', 'price' => 165, 'currency' => 'EUR', 'duration' => '3h15', 'travel_class' => 'Economy', 'status' => 'scheduled'],
            ],
            'CDG-TUN' => [
                ['airline' => 'Air France', 'flight_number' => 'AF1084', 'departure' => 'CDG', 'dep_time' => $date . ' 10:30:00', 'arrival' => 'TUN', 'arr_time' => $date . ' 13:45:00', 'price' => 195, 'currency' => 'EUR', 'duration' => '3h15', 'travel_class' => 'Economy', 'status' => 'scheduled'],
            ],
            'LYS-MRS' => [
                ['airline' => 'Air France', 'flight_number' => 'AF9402', 'departure' => 'LYS', 'dep_time' => $date . ' 09:00:00', 'arrival' => 'MRS', 'arr_time' => $date . ' 09:55:00', 'price' =>  85, 'currency' => 'EUR', 'duration' => '55min', 'travel_class' => 'Economy', 'status' => 'scheduled'],
            ],
            'MRS-LYS' => [
                ['airline' => 'Air France', 'flight_number' => 'AF9403', 'departure' => 'MRS', 'dep_time' => $date . ' 10:30:00', 'arrival' => 'LYS', 'arr_time' => $date . ' 11:25:00', 'price' =>  85, 'currency' => 'EUR', 'duration' => '55min', 'travel_class' => 'Economy', 'status' => 'scheduled'],
            ],
            'CDG-LYS' => [
                ['airline' => 'Air France', 'flight_number' => '7362',  'departure' => 'CDG', 'dep_time' => $date . ' 07:25:00', 'arrival' => 'LYS', 'arr_time' => $date . ' 13:40:00', 'price' =>  89, 'currency' => 'EUR', 'duration' => '6h15', 'travel_class' => 'Economy', 'status' => 'scheduled'],
                ['airline' => 'Lufthansa',  'flight_number' => '2227',  'departure' => 'CDG', 'dep_time' => $date . ' 09:10:00', 'arrival' => 'LYS', 'arr_time' => $date . ' 12:50:00', 'price' =>  95, 'currency' => 'EUR', 'duration' => '3h40', 'travel_class' => 'Economy', 'status' => 'scheduled'],
            ],
        ];

        $key = $dep . '-' . $arr;

        if (isset($mockFlights[$key])) {
            return $mockFlights[$key];
        }

        // Mock générique pour toute autre route
        return [
            [
                'airline'       => 'Vol Direct',
                'flight_number' => 'FL' . rand(100, 999),
                'departure'     => $dep,
                'dep_time'      => $date . ' 09:00:00',
                'arrival'       => $arr,
                'arr_time'      => $date . ' 11:00:00',
                'price'         => rand(100, 300),
                'currency'      => 'EUR',
                'duration'      => '2h00',
                'travel_class'  => 'Economy',
                'status'        => 'scheduled',
            ],
        ];
    }
}