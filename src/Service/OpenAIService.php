<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAIService
{
    private string $apiKey;

    public function __construct(
        private HttpClientInterface $client,
        string $apiKey
    ) {
        // Safe trimming of the API key
        $this->apiKey = trim($apiKey);
    }

    public function generateMatchMessage(array $data): string
    {
        $nom = $data['nom'] ?? 'cette activité';
        $cat = $data['categorie'] ?? 'découverte';
        $loc = $data['localisation'] ?? 'Tunisie';
        $prix = $data['prix'] ?? '0';
        $description = $data['description'] ?? '';

        $prompt = "L'utilisateur vient de choisir '$nom' à $loc (Catégorie: $cat, Prix: $prix DT). 
                Description: $description
                
                Rédige un message court (3 phrases) en français comme si tu étais un coach de voyage personnel.
                1. Félicite l'utilisateur avec beaucoup d'enthousiasme (ex: 'Excellent choix !').
                2. Analyse pourquoi ce choix (basé sur la catégorie et le prix) montre que l'utilisateur a un goût raffiné.
                3. Donne un conseil personnel sur l'ambiance de cette activité spécifique.
                
                Sois chaleureux, inspirant et utilise le tutoiement ('tu') pour créer une proximité.";

        return $this->callGroqAPI($prompt);
    }

    /**
     * Call Groq API with proper error handling
     */
    private function callGroqAPI(string $prompt): string
    {
        try {
            $response = $this->client->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.1-8b-instant',
                    'max_tokens' => 200,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Tu es un expert en tourisme tunisien et coach de voyage enthousiaste.'],
                        ['role' => 'user', 'content' => $prompt]
                    ]
                ],
                'timeout' => 10,
            ]);

            $result = $response->toArray();
            
            // Check if response is valid
            if (isset($result['choices'][0]['message']['content'])) {
                return trim($result['choices'][0]['message']['content']);
            }

            return $this->getDefaultMessage();

        } catch (\Exception $e) {
            // Log the error for debugging
            error_log('Groq API Error: ' . $e->getMessage());
            return $this->getDefaultMessage();
        }
    }

    private function getDefaultMessage(): string
    {
        $messages = [
            "Excellent choix ! Vous avez un goût remarquable pour les expériences authentiques. Préparez-vous à vivre un moment inoubliable !",
            "Bravo ! Ce choix reflète une vraie passion pour la découverte. Vous êtes prêt à créer des souvenirs exceptionnels.",
            "Magnifique sélection ! Vous savez exactement ce qui vous fait vibrer. C'est le moment de vivre cette aventure intensément !"
        ];
        return $messages[array_rand($messages)];
    }
}