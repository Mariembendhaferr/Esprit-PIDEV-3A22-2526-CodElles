<?php

namespace App\Controller;

use App\Service\ExplorerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/explorer', name: 'app_explorer')]
class ExplorerController extends AbstractController
{
    public function __construct(
        private readonly ExplorerService $explorerService,
    ) {}

    /* ──────────────────────────────────────────────────────────────
       PAGE PRINCIPALE
    ────────────────────────────────────────────────────────────── */

    #[Route('', name: '', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('voyage/explorer.html.twig', [
            'categories' => ExplorerService::CATEGORIES,
        ]);
    }

    /* ──────────────────────────────────────────────────────────────
       API INTERNE — AUTOCOMPLETE (appelée en AJAX)
       GET /explorer/autocomplete?q=Paris
    ────────────────────────────────────────────────────────────── */

    #[Route('/autocomplete', name: '_autocomplete', methods: ['GET'])]
    public function autocomplete(Request $request): JsonResponse
    {
        $query = trim((string) $request->query->get('q', ''));

        if (mb_strlen($query) < 2) {
            return $this->json([]);
        }

        $suggestions = $this->explorerService->autocomplete($query);

        return $this->json($suggestions);
    }

    /* ──────────────────────────────────────────────────────────────
       API INTERNE — TOP 3 (appelée en AJAX)
       GET /explorer/results?location=Paris&category=cafe
       
       ⚠️ NOTE : Limité à 3 résultats pour économiser les requêtes Foursquare
    ────────────────────────────────────────────────────────────── */

    #[Route('/results', name: '_results', methods: ['GET'])]
    public function results(Request $request): JsonResponse
    {
        $location = trim((string) $request->query->get('location', ''));
        $category = trim((string) $request->query->get('category', ''));

        // ── Validation de base ──────────────────────────────────
        if ($location === '' || $category === '') {
            return $this->json(
                ['error' => 'Les paramètres "location" et "category" sont obligatoires.'],
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!array_key_exists($category, ExplorerService::CATEGORIES)) {
            return $this->json(
                ['error' => 'Catégorie invalide. Valeurs acceptées : ' . implode(', ', array_keys(ExplorerService::CATEGORIES))],
                Response::HTTP_BAD_REQUEST
            );
        }

        // ── Appel Service (retourne 3 résultats maximum) ─────────
        try {
            $places = $this->explorerService->getTopPlaces($location, $category);

            // Log de la consommation (optionnel)
            $this->addFlash('info', sprintf('%d établissements trouvés à %s', count($places), $location));

            return $this->json([
                'location'  => $location,
                'category'  => $category,
                'label'     => ExplorerService::CATEGORIES[$category]['label'],
                'count'     => count($places),
                'places'    => $places,
            ]);

        } catch (\InvalidArgumentException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);

        } catch (\RuntimeException $e) {
            $this->addFlash('warning', 'Service temporairement indisponible. Affichage de résultats de démonstration.');
            return $this->json(['error' => $e->getMessage()], Response::HTTP_SERVICE_UNAVAILABLE);
        }
    }

    /* ──────────────────────────────────────────────────────────────
       API OPTIONNELLE — Détails d'un lieu spécifique
       GET /explorer/place/{fsqId}
       Utile pour afficher une fiche détaillée sur une page dédiée
    ────────────────────────────────────────────────────────────── */

    #[Route('/place/{fsqId}', name: '_place_details', methods: ['GET'])]
    public function placeDetails(string $fsqId): JsonResponse
    {
        try {
            $details = $this->explorerService->getPlaceDetails($fsqId);
            
            return $this->json([
                'success' => true,
                'data' => $details,
            ]);
            
        } catch (\RuntimeException $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_NOT_FOUND);
        }
    }
    
}