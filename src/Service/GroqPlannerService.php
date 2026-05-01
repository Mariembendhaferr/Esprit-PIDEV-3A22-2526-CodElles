<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GroqPlannerService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $groqApiKey,
    ) {}

    /**
     * @param array<string, mixed> $preferences
     * @return array<string, mixed>
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
        $content = isset($data['choices'][0]['message']['content']) ? (string) $data['choices'][0]['message']['content'] : '';

        // ✅ Fix: on s'assure que $content est bien une string avant trim()
        $content = preg_replace('/```json\s*/i', '', $content) ?? '';
        $content = preg_replace('/```\s*/i', '', $content) ?? '';
        $content = trim($content);

        $plan = json_decode($content, true);

        if (!is_array($plan)) {
            throw new \RuntimeException('Impossible de décoder la réponse de l\'IA.');
        }

        return $plan;
    }

    /**
     * @param array<string, mixed> $p
     */
    private function buildPrompt(array $p): string
    {
        // ✅ Fix: cast explicite en string pour éviter trim() sur valeur non-string
        $destination  = (string) ($p['destination'] ?? '');
        $budget       = (string) ($p['budget'] ?? '');
        $nbPersonnes  = (string) ($p['nb_personnes'] ?? '');
        $duree        = (string) ($p['duree'] ?? '');
        $theme        = (string) ($p['theme'] ?? '');
        $hebergement  = (string) ($p['hebergement'] ?? '');
        $rythme       = (string) ($p['rythme'] ?? '');
        $description  = (string) ($p['description'] ?? '');

        return <<<PROMPT
Génère un plan de voyage personnalisé en JSON strictement valide selon ce format exact.

Préférences du voyageur :
- Destination : {$destination}
- Budget total : {$budget} DT
- Nombre de personnes : {$nbPersonnes}
- Durée : {$duree} jours
- Thème : {$theme}
- Style hébergement : {$hebergement}
- Rythme : {$rythme}
- Description libre : {$description}

Génère UNIQUEMENT ce JSON (sans texte avant ou après) :
{
  "destination": "Nom complet destination",
  "pays": "Nom du pays",
  "theme": "Thème du voyage",
  "duree": {$duree},
  "nb_personnes": {$nbPersonnes},
  "budget_total": "XXX DT",
  "budget_par_personne": "XXX DT",
  "style_hebergement": "...",
  "resume": "Un résumé poétique et inspirant du voyage en 2-3 phrases",
  "jours": [
    {
      "jour": 1,
      "titre": "Titre évocateur de la journée",
      "matin": { "activite": "...", "description": "...", "image_query": "..." },
      "apres_midi": { "activite": "...", "description": "...", "image_query": "..." },
      "soir": { "activite": "...", "description": "...", "image_query": "..." },
      "hebergement": "...",
      "budget_jour": "XXX € / personne",
      "conseil": "..."
    }
  ],
  "conseils_generaux": ["conseil 1", "conseil 2", "conseil 3"],
  "meilleure_periode": "..."
}

Génère exactement {$duree} jours. Sois précis, inspirant et luxueux dans les descriptions.
PROMPT;
    }
}