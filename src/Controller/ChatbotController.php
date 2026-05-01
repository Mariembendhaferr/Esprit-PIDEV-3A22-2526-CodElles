<?php

namespace App\Controller;

use App\Service\GroqService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/chatbot', name: 'app_chatbot_')]
class ChatbotController extends AbstractController
{
    public function __construct(
        private readonly GroqService $groqService,
    ) {}

    /**
     * Affiche la page du chatbot (votre design Twig).
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('/voyage/chat.html.twig');
    }

    /**
     * Point d'entrée AJAX : reçoit l'historique, retourne la réponse IA.
     *
     * Payload attendu (JSON) :
     * {
     *   "messages": [
     *     {"role": "user",      "content": "Bonjour"},
     *     {"role": "assistant", "content": "Bonjour ! ..."},
     *     {"role": "user",      "content": "Je cherche un safari"}
     *   ]
     * }
     *
     * Réponse :
     * { "reply": "Voici nos safaris..." }
     * ou en cas d'erreur :
     * { "error": "Message d'erreur" }
     */
    #[Route('/message', name: 'message', methods: ['POST'])]
    public function message(Request $request): JsonResponse
    {
        // Vérification que la requête vient bien du JS (sécurité basique)
        if (!$request->isXmlHttpRequest()) {
            return $this->json(['error' => 'Requête non autorisée.'], Response::HTTP_FORBIDDEN);
        }

        // Décodage du corps JSON
        $payload = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($payload['messages'])) {
            return $this->json(['error' => 'Payload invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $conversationHistory = $payload['messages'];

        // Validation basique : chaque message doit avoir role + content
        foreach ($conversationHistory as $message) {
            if (!isset($message['role'], $message['content'])) {
                return $this->json(['error' => 'Format de message invalide.'], Response::HTTP_BAD_REQUEST);
            }

            if (!in_array($message['role'], ['user', 'assistant'], true)) {
                return $this->json(['error' => 'Rôle invalide.'], Response::HTTP_BAD_REQUEST);
            }
        }

        // Limiter l'historique aux 20 derniers messages pour éviter de dépasser le contexte
        $conversationHistory = array_slice($conversationHistory, -20);

        try {
            $reply = $this->groqService->chat($conversationHistory);

            return $this->json(['reply' => $reply]);

        } catch (\RuntimeException $e) {
            // On logue l'erreur sans l'exposer complètement à l'utilisateur
            return $this->json(
                ['error' => 'Le service IA est temporairement indisponible. Veuillez réessayer.'],
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }
    }
}
