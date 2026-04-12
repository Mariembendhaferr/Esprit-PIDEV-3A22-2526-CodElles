<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAIService
{
    public function __construct(
        private HttpClientInterface $client,
        private string $apiKey
    ) {}

    public function generateDescription(string $activityName, string $location, string $customPrompt = ''): string
    {
        $prompt = $customPrompt ?: "Écris une description courte et attrayante (max 150 mots) pour cette activité touristique : \"$activityName\" à $location.";

        try {
            $response = $this->client->request('POST',
                'https://api.groq.com/openai/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'model'      => 'llama-3.1-8b-instant',
                        'max_tokens' => 200,
                        'messages'   => [
                            [
                                'role'    => 'system',
                                'content' => 'Tu es un expert en tourisme. Tu rédiges des messages courts, chaleureux et inspirants en français.'
                            ],
                            [
                                'role'    => 'user',
                                'content' => $prompt
                            ]
                        ]
                    ]
                ]
            );

            $data = $response->toArray();
            return trim($data['choices'][0]['message']['content'] ?? '');

        } catch (\Exception $e) {
            return '';
        }
    }

    public function generateMatchMessage(string $activityName, string $category, string $location, string $duration, string $price): string
    {
        $prompt = "L'utilisateur a choisi \"$activityName\" comme activité parfaite (catégorie: $category, lieu: $location, durée: {$duration}min, prix: {$price}DT). Commence par \"Excellent choix !\" et explique pourquoi c'est un match parfait pour lui.";

        try {
            $response = $this->client->request('POST',
                'https://api.groq.com/openai/v1/chat/completions', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type'  => 'application/json',
                    ],
                    'json' => [
                        'model'      => 'llama-3.1-8b-instant',
                        'max_tokens' => 150,
                        'messages'   => [
                            [
                                'role'    => 'system',
                                'content' => 'Tu es un expert en tourisme. Réponds toujours en français, 3-4 phrases max, chaleureux et inspirant.'
                            ],
                            [
                                'role'    => 'user',
                                'content' => $prompt
                            ]
                        ]
                    ]
                ]
            );

            $data = $response->toArray();
            return trim($data['choices'][0]['message']['content'] ?? 'Cette activité correspond parfaitement à vos préférences !');

        } catch (\Exception $e) {
            return 'Cette activité correspond parfaitement à vos préférences — lancez-vous dans l\'aventure !';
        }
    }
}