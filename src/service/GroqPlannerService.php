<?php
// src/Service/GroqPlannerService.php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GroqPlannerService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $groqApiKey,
    ) {}

    /**
     * Génère un plan journalier complet basé sur les préférences utilisateur.
     * Retourne un tableau PHP structuré.
     */
    public function generatePlan(array $preferences): array
    {
        $prompt = $this->buildPrompt($preferences);

        $response = $this->httpClient->request('POST',
            'https://api.groq.com/openai/v1/chat/completions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => 'llama-3.3-70b-versatile',
                    'temperature' => 0.7,
                    'max_tokens'  => 4000,
                    'messages'    => [
                        [
                            'role'    => 'system',
                            'content' => 'Tu es un expert en voyages de luxe. Tu génères des plans de voyage détaillés et personnalisés en JSON uniquement. Tu ne réponds JAMAIS avec du texte, uniquement du JSON valide sans balises markdown.',
                        ],
                        [
                            'role'    => 'user',
                            'content' => $prompt,
                        ],
                    ],
                ],
                'timeout' => 60,
            ]
        );

        $data    = $response->toArray();
        $content = $data['choices'][0]['message']['content'] ?? '';

        // Nettoyer les balises markdown si présentes
        $content = preg_replace('/```json\s*/i', '', $content);
        $content = preg_replace('/```\s*/i', '', $content);
        $content = trim($content);

        $plan = json_decode($content, true);

        if (!$plan) {
            throw new \RuntimeException('Impossible de décoder la réponse de l\'IA.');
        }

        return $plan;
    }

    private function buildPrompt(array $p): string
    {
        return <<<PROMPT
Génère un plan de voyage personnalisé en JSON strictement valide selon ce format exact.

Préférences du voyageur :
- Destination : {$p['destination']}
- Budget total : {$p['budget']} DT
- Nombre de personnes : {$p['nb_personnes']}
- Durée : {$p['duree']} jours
- Thème : {$p['theme']}
- Style hébergement : {$p['hebergement']}
- Rythme : {$p['rythme']}
- Description libre : {$p['description']}

Génère UNIQUEMENT ce JSON (sans texte avant ou après) :
{
  "destination": "Nom complet destination",
  "pays": "Nom du pays",
  "theme": "Thème du voyage",
  "duree": {$p['duree']},
  "nb_personnes": {$p['nb_personnes']},
  "budget_total": "XXX DT",
  "budget_par_personne": "XXX DT",
  "style_hebergement": "...",
  "resume": "Un résumé poétique et inspirant du voyage en 2-3 phrases",
  "jours": [
    {
      "jour": 1,
      "titre": "Titre évocateur de la journée",
      "matin": {
        "activite": "Nom de l'activité",
        "description": "Description détaillée et inspirante",
        "image_query": "mots-clés anglais pour chercher une image Unsplash (ex: marrakech medina morocco)"
      },
      "apres_midi": {
        "activite": "Nom de l'activité",
        "description": "Description détaillée",
        "image_query": "mots-clés anglais Unsplash"
      },
      "soir": {
        "activite": "Nom de l'activité",
        "description": "Description détaillée",
        "image_query": "mots-clés anglais Unsplash"
      },
      "hebergement": "Nom et type d'hébergement recommandé",
      "budget_jour": "XXX € / personne",
      "conseil": "Un conseil pratique pour cette journée"
    }
  ],
  "conseils_generaux": ["conseil 1", "conseil 2", "conseil 3"],
  "meilleure_periode": "Meilleure période pour visiter"
}

Génère exactement {$p['duree']} jours. Sois précis, inspirant et luxueux dans les descriptions.
PROMPT;
    }
}
