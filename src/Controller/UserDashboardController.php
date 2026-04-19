<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\WeatherService;
use Symfony\Component\HttpFoundation\Request;
class UserDashboardController extends AbstractController
{
    #[Route('/user/dashboard', name: 'user_dashboard')]
    public function index(
         Request $request,
        SessionInterface $session,
        UserRepository $userRepository,
        WeatherService $weatherService 
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
        return $this->render('user/dashboard.html.twig', [
            'user' => $user,
            'weather' => $weather,
            'current_city' => $city, 
        ]);
    }

}