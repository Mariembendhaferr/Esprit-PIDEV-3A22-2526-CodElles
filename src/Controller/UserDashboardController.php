<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\WeatherService;
use App\Service\CarbonService;
use Symfony\Component\HttpFoundation\Request;

class UserDashboardController extends AbstractController
{
    #[Route('/user/dashboard', name: 'user_dashboard')]
    public function index(
        Request $request,
        SessionInterface $session,
        UserRepository $userRepository,
        WeatherService $weatherService,
        CarbonService $carbonService
    ): Response {
        $userId = $session->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('login');
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->redirectToRoute('login');
        }

        $city = $request->query->get('weather_city', 'Tunis');
        $weather = $weatherService->getWeather($city, 'fr');

        // Carbon Calculator avec coordonnées GPS
        $fromLat = $request->query->get('from_lat');
        $fromLon = $request->query->get('from_lon');
        $toLat = $request->query->get('to_lat');
        $toLon = $request->query->get('to_lon');
        
        $carbonEstimate = null;
        $fromCity = $request->query->get('from_city', 'Tunis');
        $toCity = $request->query->get('to_city', 'Paris');
        
        if ($fromLat && $fromLon && $toLat && $toLon) {
            $distance = $this->calculateDistance($fromLat, $fromLon, $toLat, $toLon);
            $carbonEstimate = $carbonService->estimateFlightFootprintByDistance($distance);
        }

        return $this->render('user/dashboard.html.twig', [
            'user' => $user,
            'weather' => $weather,
            'current_city' => $city,
            'carbonEstimate' => $carbonEstimate,
            'fromLat' => $fromLat,
            'fromLon' => $fromLon,
            'toLat' => $toLat,
            'toLon' => $toLon,
            'fromCity' => $fromCity,
            'toCity' => $toCity,
        ]);
    }

    private function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }
}