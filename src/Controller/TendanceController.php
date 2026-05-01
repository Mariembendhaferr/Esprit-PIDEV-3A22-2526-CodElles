<?php

namespace App\Controller;

use App\Service\SerpApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin', name: 'admin_')]
class TendanceController extends AbstractController
{
    public function __construct(
        private SerpApiService $serpApiService
    ) {}

    

    // ─────────────────────────────────────────────
    //  2. LISTE DESTINATIONS TENDANCES
    // ─────────────────────────────────────────────
    #[Route('/destinations/tendances', name: 'destinations_tendances')]
    public function tendances(): Response
    {
        $destinations = $this->serpApiService->getTrendingDestinations();

        return $this->render('admin/tendances.html.twig', [
            'destinations' => $destinations,
        ]);
    }

    // ─────────────────────────────────────────────
    //  3. DÉTAIL D'UNE DESTINATION
    // ─────────────────────────────────────────────
    #[Route('/destinations/{destination}', name: 'destination_detail')]
    public function detail(string $destination): Response
    {
        $destination = urldecode($destination);

        $trends = $this->serpApiService->getDestinationTrends($destination);
        $news   = $this->serpApiService->getDestinationNews($destination);
        $places = $this->serpApiService->getDestinationPlaces($destination);
        $forums = $this->serpApiService->getDestinationForums($destination);

        return $this->render('admin/detail.html.twig', [
            'destination'   => $destination,
            'timelineData'  => $trends['timeline'],
            'regionData'    => $trends['regions'],
            'newsResults'   => $news,
            'localResults'  => $places['local'],
            'organicPlaces' => $places['organic'],
            'forumsResults' => $forums,
        ]);
    }
}
