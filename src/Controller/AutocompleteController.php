<?php
// src/Controller/AutocompleteController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/autocomplete')]
class AutocompleteController extends AbstractController
{
    #[Route('/destinations', name: 'app_autocomplete_destinations')]
    public function destinations(Request $request): JsonResponse
    {
        $query = strtolower(trim($request->query->get('query', '')));
        
        // 👇 SIMULATED DATA (No DB change - safe for Java app)
        $all = [
            ['id' => 1, 'text' => 'Paris, France'],
            ['id' => 2, 'text' => 'Tunis, Tunisia'],
            ['id' => 3, 'text' => 'Dubai, UAE'],
            ['id' => 4, 'text' => 'Barcelona, Spain'],
            ['id' => 5, 'text' => 'Rome, Italy'],
            ['id' => 6, 'text' => 'London, UK'],
            ['id' => 7, 'text' => 'Istanbul, Turkey'],
            ['id' => 8, 'text' => 'Marrakech, Morocco'],
        ];
        
        $results = array_filter($all, fn($d) => str_contains(strtolower($d['text']), $query));
        
        return $this->json(array_slice(array_values($results), 0, 5));
    }
}