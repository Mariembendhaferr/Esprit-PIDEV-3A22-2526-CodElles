<?php
// src/Service/CarbonService.php
namespace App\Service;

use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class CarbonService
{
    private $cache;

    // Simplified CO2 estimates per km for different flight classes (kg CO2 per passenger-km)
    private const CO2_PER_KM = [
        'economy' => 0.115,
        'premium_economy' => 0.150,
        'business' => 0.230,
        'first' => 0.350,
    ];

    // Airport coordinates for distance calculation (simplified)
    private const AIRPORTS = [
        'TUN' => ['lat' => 36.851, 'lon' => 10.227], // Tunis
        'CDG' => ['lat' => 49.009, 'lon' => 2.547],   // Paris
        'LHR' => ['lat' => 51.470, 'lon' => -0.454],  // London
        'DXB' => ['lat' => 25.253, 'lon' => 55.364],  // Dubai
        'IST' => ['lat' => 41.275, 'lon' => 28.751],  // Istanbul
        'FCO' => ['lat' => 41.800, 'lon' => 12.239],  // Rome
        'BCN' => ['lat' => 41.297, 'lon' => 2.078],   // Barcelona
        'MAD' => ['lat' => 40.472, 'lon' => -3.560],  // Madrid
        'AMS' => ['lat' => 52.308, 'lon' => 4.764],   // Amsterdam
        'FRA' => ['lat' => 50.038, 'lon' => 8.562],   // Frankfurt
        // Add more as needed
    ];

    public function __construct()
    {
        $this->cache = new FilesystemAdapter('carbon', 86400); // Cache 24h
    }

    /**
     * Estimate CO2 for a flight using simple distance-based calculation
     * @param string $from Airport code (e.g., 'TUN')
     * @param string $to Airport code (e.g., 'CDG')
     * @param string $class 'economy', 'business', etc.
     */
    public function estimateFlightFootprint(string $from, string $to, string $class = 'economy'): ?array
    {
        $from = strtoupper($from);
        $to = strtoupper($to);
        $class = strtolower($class);

        return $this->cache->get("carbon_{$from}_{$to}_{$class}", function () use ($from, $to, $class) {
            // 1. Get airport coordinates
            $fromCoords = self::AIRPORTS[$from] ?? null;
            $toCoords = self::AIRPORTS[$to] ?? null;

            if (!$fromCoords || !$toCoords) {
                // Fallback: random estimate for unknown airports
                return $this->getFallbackEstimate($class);
            }

            // 2. Calculate distance using Haversine formula
            $distanceKm = $this->calculateDistance(
                $fromCoords['lat'], $fromCoords['lon'],
                $toCoords['lat'], $toCoords['lon']
            );

            // 3. Calculate CO2 based on class
            $co2PerKm = self::CO2_PER_KM[$class] ?? self::CO2_PER_KM['economy'];
            $co2Kg = round($distanceKm * $co2PerKm, 1);

            // 4. Generate helpful info
            $trees = ceil($co2Kg / 40); // ~40kg CO2 absorbed per tree per year
            $carMonths = round($co2Kg / 150); // ~150kg CO2 per month of average car use

            return [
                'co2_kg' => $co2Kg,
                'trees_to_offset' => max(1, $trees),
                'comparison' => "≈ {$carMonths} mois de conduite en voiture",
                'eco_tip' => $this->getEcoTip($co2Kg, $distanceKm),
                'distance_km' => round($distanceKm),
            ];
        });
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }

    private function getEcoTip(float $co2Kg, float $distanceKm): string
    {
        if ($distanceKm < 500) {
            return 'Pour les courtes distances, le train est souvent plus écologique ! 🚄';
        }
        if ($co2Kg < 200) {
            return 'Bon choix ! Impact modéré. Pensez à compenser votre carbone. 🌱';
        }
        if ($co2Kg < 500) {
            return 'Impact moyen. Envisagez des programmes de compensation carbone. 🌍';
        }
        return 'Impact élevé. Compensez votre vol via des projets certifiés ! ♻️';
    }

    private function getFallbackEstimate(string $class): array
    {
        $co2PerKm = self::CO2_PER_KM[$class] ?? self::CO2_PER_KM['economy'];
        // Assume average flight of 1500 km for unknown routes
        $co2Kg = round(1500 * $co2PerKm, 1);
        
        return [
            'co2_kg' => $co2Kg,
            'trees_to_offset' => max(1, ceil($co2Kg / 40)),
            'comparison' => '≈ ' . round($co2Kg / 150) . ' mois de conduite en voiture',
            'eco_tip' => 'Estimation basée sur une distance moyenne. 🌐',
            'distance_km' => 1500,
        ];
    }
}