<?php
// src/Controller/Api/SentimentController.php
namespace App\Controller\Api;

use App\Service\SentimentService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SentimentController extends AbstractController
{
    #[Route('/api/sentiment', name: 'api_sentiment', methods: ['POST'])]
    public function analyze(Request $request, SentimentService $sentimentService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $text = $data['text'] ?? '';
        
        if (empty($text)) {
            return new JsonResponse(['error' => 'No text provided'], 400);
        }
        
        $result = $sentimentService->analyze($text);
        
        return new JsonResponse($result);
    }
}