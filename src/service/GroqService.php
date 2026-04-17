<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class GroqService
{
    private const GROQ_API_URL = 'https://api.groq.com/openai/v1/chat/completions';

    private const SYSTEM_PROMPT = <<<PROMPT
Tu es le Concierge IA de Doura Mondo, une agence de voyages de luxe spécialisée dans les voyages d'exception.

Ton rôle :
- Conseiller les clients avec élégance et expertise sur leurs voyages
- Proposer des destinations premium : Afrique (safaris), Océan Indien (Maldives, Seychelles, La Réunion, Maurice), Asie (Japon, Bali, Thaïlande), Amériques, Europe
- Suggérer des expériences sur mesure, des hôtels de luxe, des activités exclusives
- Répondre aux questions sur les tarifs, disponibilités, visas, meilleure période pour voyager
- En cas de demande de devis précis ou de réservation, proposer de transférer à un conseiller humain

Ton ton : raffiné, chaleureux, inspirant. Tu tutoies si le client l'initie, sinon vouvoiement.
Tu réponds toujours en français.
Tes réponses sont concises mais riches (3 à 6 phrases maximum), jamais de listes à puces sauf si explicitement demandé.
Tu n'inventes pas de prix précis — tu parles en fourchettes ou proposes un devis personnalisé.
PROMPT;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $groqApiKey,
        private readonly string $groqModel,
    ) {}

    /**
     * Envoie l'historique de conversation à Groq et retourne la réponse du modèle.
     *
     * @param array $conversationHistory Tableau de messages [['role' => 'user'|'assistant', 'content' => '...']]
     * @return string La réponse textuelle du modèle
     * @throws \RuntimeException En cas d'erreur API
     */
    public function chat(array $conversationHistory): string
    {
        // Construction des messages avec le system prompt en tête
        $messages = [
            ['role' => 'system', 'content' => self::SYSTEM_PROMPT],
            ...$conversationHistory,
        ];

        try {
            $response = $this->httpClient->request('POST', self::GROQ_API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => $this->groqModel,
                    'messages'    => $messages,
                    'max_tokens'  => 1024,
                    'temperature' => 0.7,
                ],
            ]);

            $data = $response->toArray();

            if (empty($data['choices'][0]['message']['content'])) {
                throw new \RuntimeException('Réponse vide reçue de Groq.');
            }

            return $data['choices'][0]['message']['content'];

        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Erreur réseau lors de l\'appel à Groq : ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur Groq : ' . $e->getMessage());
        }
    }
}
