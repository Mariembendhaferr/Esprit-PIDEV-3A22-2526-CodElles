<?php
// src/Controller/TravelInnovationController.php
namespace App\Controller;

use App\Service\CarbonService;
use App\Service\EventsService;
use App\Service\TranslateService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/travel-innovation')]
class TravelInnovationController extends AbstractController
{
    #[Route('/demo', name: 'app_travel_demo')]
    public function demo(
        CarbonService $carbon,
        EventsService $events,
        TranslateService $translate
    ): Response {
        // Demo data - replace with real user input in production
        $flightData = $carbon->estimateFlightFootprint('TUN', 'CDG', 'economy');
        $eventsData = $events->findNearbyEvents(36.8065, 10.1815, '2024-07-01', '2024-07-10');
        $phrases = $translate->getTravelPhrases('fr');
        
        return $this->render('travel/innovation_demo.html.twig', [
            'flight' => $flightData,
            'events' => $eventsData,
            'phrases' => $phrases,
            'demo_mode' => true // Flag to show fallback data in UI
        ]);
    }

    #[Route('/translate-api', name: 'app_translate_api', methods: ['POST'])]
    public function translateApi(Request $request, TranslateService $translate): Response
    {
        $text = $request->request->get('text');
        $to = $request->request->get('to', 'fr');
        
        return $this->json([
            'original' => $text,
            'translated' => $translate->translate($text, 'en', $to)
        ]);
    }
}