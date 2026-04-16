<?php

namespace App\Controller;

use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class QuizController extends AbstractController
{
    private const VALID_MODELS = [
        'llama-3.3-70b-versatile',
        'llama-3.1-8b-instant',
        'meta-llama/llama-4-scout-17b-16e-instruct',
    ];

    private const POINTS_PER_CORRECT = 10;
    private const QUESTIONS_COUNT    = 5;
    private const CHOICES_COUNT      = 4;
    private const PROMO_MIN_SCORE    = 4;
    private const PROMO_DISCOUNT     = 15;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $groqApiKey,
        private readonly string $groqModel = 'llama-3.3-70b-versatile',
    ) {
        if (!in_array($this->groqModel, self::VALID_MODELS, true)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Modèle Groq invalide "%s". Modèles valides : %s',
                    $this->groqModel,
                    implode(', ', self::VALID_MODELS)
                )
            );
        }
    }

    #[Route('/quiz/{id}', name: 'app_quiz', requirements: ['id' => '\d+'])]
    public function index(int $id, ReservationRepository $reservationRepo): Response
    {
        $reservation = $reservationRepo->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException('Réservation introuvable');
        }

        return $this->render('quiz/index.html.twig', [
            'reservation' => $reservation,
            'client'      => $reservation->getClient(),
            'destination' => $reservation->getVoyage()->getDestination(),
        ]);
    }

    #[Route('/quiz/generate-questions', name: 'app_quiz_generate', methods: ['POST'])]
    public function generateQuestions(
        Request $request,
        ReservationRepository $reservationRepo
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(
                    ['error' => 'JSON invalide: ' . json_last_error_msg()],
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            $reservationId = $data['reservation_id'] ?? null;

            if (!$reservationId) {
                return $this->json(
                    ['error' => 'ID réservation manquant'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $reservation = $reservationRepo->find($reservationId);
            if (!$reservation) {
                return $this->json(
                    ['error' => 'Réservation introuvable'],
                    Response::HTTP_NOT_FOUND
                );
            }

            $destination = $reservation->getVoyage()->getDestination();

            $questions = $this->callGroqAPI($destination);
            
            $request->getSession()->set('quiz_questions_' . $reservationId, $questions);
            $request->getSession()->save();

            return $this->json([
                'success'   => true,
                'questions' => $questions,
            ]);
            
        } catch (\Throwable $e) {
            error_log('Erreur generateQuestions: ' . $e->getMessage());
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/quiz/submit', name: 'app_quiz_submit', methods: ['POST'])]
    public function submitQuiz(
        Request $request,
        EntityManagerInterface $em,
        ReservationRepository $reservationRepo
    ): JsonResponse {
        try {
            $data = json_decode($request->getContent(), true) ?? [];
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->json(
                    ['error' => 'JSON invalide: ' . json_last_error_msg()],
                    Response::HTTP_BAD_REQUEST
                );
            }
            
            $reservationId = $data['reservation_id'] ?? null;
            $userAnswers   = $data['answers'] ?? [];

            if (!$reservationId) {
                return $this->json(
                    ['error' => 'ID réservation manquant'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $reservation = $reservationRepo->find($reservationId);
            if (!$reservation) {
                return $this->json(
                    ['error' => 'Réservation introuvable'],
                    Response::HTTP_NOT_FOUND
                );
            }

            $session    = $request->getSession();
            $sessionKey = 'quiz_questions_' . $reservationId;
            $questions  = $session->get($sessionKey);

            if (empty($questions) || !is_array($questions)) {
                return $this->json(
                    ['error' => 'Questions du quiz non trouvées ou expirées. Veuillez recommencer.'],
                    Response::HTTP_BAD_REQUEST
                );
            }

            $score   = 0;
            $total   = count($questions);
            $results = [];

            foreach ($questions as $index => $question) {
                $correctAnswer = $question['correct'];
                $userAnswer    = (string) ($userAnswers[$index] ?? '');
                $isCorrect     = $this->normalizeAnswer($userAnswer) === $this->normalizeAnswer($correctAnswer);

                if ($isCorrect) {
                    ++$score;
                }

                $results[] = [
                    'question'       => $question['question'],
                    'user_answer'    => $userAnswer,
                    'correct_answer' => $correctAnswer,
                    'is_correct'     => $isCorrect,
                    'choices'        => $question['choices'],
                ];
            }

            $pointsEarned = $score * self::POINTS_PER_CORRECT;
            $client       = $reservation->getClient();
            $client->setPoints($client->getPoints() + $pointsEarned);
            $em->flush();

            $session->remove($sessionKey);

            $percentage = $total > 0 ? (int) round(($score / $total) * 100) : 0;

            $response = [
                'success'      => true,
                'score'        => $score,
                'total'        => $total,
                'pointsEarned' => $pointsEarned,
                'totalPoints'  => $client->getPoints(),
                'percentage'   => $percentage,
                'results'      => $results,
            ];

            if ($score >= self::PROMO_MIN_SCORE) {
                $discount = $score === self::QUESTIONS_COUNT
                    ? self::PROMO_DISCOUNT + 5
                    : self::PROMO_DISCOUNT;
                
                $promoCode = $this->generateShareablePromoCode();
                $expiresAt = (new \DateTimeImmutable('+30 days'))->format('d/m/Y');
                
                // Sauvegarder dans le fichier JSON
                $this->savePromoCodeToFile($promoCode, $discount, $expiresAt);
                
                $response['promo'] = [
                    'code'       => $promoCode,
                    'discount'   => $discount,
                    'expires_at' => $expiresAt,
                    'message'    => sprintf(
                        '🎉 Félicitations ! Vous avez obtenu %d/5. Voici votre code promo de %d%% de réduction à partager avec vos amis !',
                        $score,
                        $discount
                    ),
                    'share_text' => sprintf(
                        '🎁 Code promo %d%% de réduction sur votre prochain voyage ! Utilisez le code : %s',
                        $discount,
                        $promoCode
                    ),
                ];
            }

            return $this->json($response);
            
        } catch (\Throwable $e) {
            error_log('Erreur submitQuiz: ' . $e->getMessage());
            return $this->json(
                ['error' => $e->getMessage()],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    private function generateShareablePromoCode(): string
    {
        $prefix = 'VOYAGE';
        $suffix = substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6);
        return strtoupper($prefix . $suffix);
    }

    /**
     * Sauvegarde le code promo dans un fichier JSON (sans BDD)
     */
    private function savePromoCodeToFile(string $code, int $discount, string $expiresAt): void
    {
        $projectDir = $this->getParameter('kernel.project_dir');
        $promoFile = $projectDir . '/var/promoCodes.json';
        
        // Créer le dossier var s'il n'existe pas
        if (!is_dir(dirname($promoFile))) {
            mkdir(dirname($promoFile), 0755, true);
        }
        
        $codes = [];
        if (file_exists($promoFile)) {
            $content = file_get_contents($promoFile);
            $codes = json_decode($content, true);
            if (!is_array($codes)) $codes = [];
        }
        
        $codes[] = [
            'code' => $code,
            'discount' => $discount,
            'expires_at' => $expiresAt,
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            'max_uses' => 10,
            'current_uses' => 0
        ];
        
        file_put_contents($promoFile, json_encode($codes, JSON_PRETTY_PRINT));
    }

    private function callGroqAPI(string $destination): array
    {
        if (empty($this->groqApiKey)) {
            throw new \RuntimeException(
                'Clé API Groq manquante. Vérifiez GROQ_API_KEY dans votre fichier .env'
            );
        }

        $allThemes = [
            ['theme' => 'GASTRONOMIE', 'exemple' => 'Quel ingrédient est indispensable dans la cuisine traditionnelle de ' . $destination . ' ?'],
            ['theme' => 'HISTOIRE', 'exemple' => 'En quelle année un événement historique majeur a-t-il marqué ' . $destination . ' ?'],
            ['theme' => 'GÉOGRAPHIE', 'exemple' => 'Quelle mer, montagne ou fleuve borde ou traverse ' . $destination . ' ?'],
            ['theme' => 'CULTURE & FÊTES', 'exemple' => 'Quelle fête ou célébration traditionnelle est la plus connue à ' . $destination . ' ?'],
            ['theme' => 'FAUNE & FLORE', 'exemple' => 'Quel animal ou plante est emblématique de ' . $destination . ' ?'],
            ['theme' => 'ARCHITECTURE', 'exemple' => 'Dans quel style architectural est construit le monument le plus visité de ' . $destination . ' ?'],
            ['theme' => 'ÉCONOMIE', 'exemple' => 'Quelle est la principale ressource économique ou industrie de ' . $destination . ' ?'],
            ['theme' => 'TRANSPORT', 'exemple' => 'Quel est le moyen de transport le plus utilisé ou le plus emblématique à ' . $destination . ' ?'],
            ['theme' => 'CLIMAT', 'exemple' => 'Quelle saison est idéale pour visiter ' . $destination . ' et pourquoi ?'],
            ['theme' => 'RELIGION & TRADITIONS', 'exemple' => 'Quelle pratique religieuse ou tradition ancestrale est répandue à ' . $destination . ' ?'],
            ['theme' => 'LANGUE & EXPRESSIONS', 'exemple' => 'Quelle langue, dialecte ou expression locale est propre à ' . $destination . ' ?'],
            ['theme' => 'SPORT', 'exemple' => 'Quel sport ou discipline est le plus pratiqué ou suivi à ' . $destination . ' ?'],
            ['theme' => 'MONNAIE & ÉCONOMIE', 'exemple' => 'Quelle monnaie ou habitude commerciale est typique de ' . $destination . ' ?'],
            ['theme' => 'MUSIQUE & ART', 'exemple' => 'Quel genre musical, instrument ou style artistique est né à ' . $destination . ' ?'],
            ['theme' => 'CHIFFRES & STATISTIQUES', 'exemple' => 'Combien de touristes visitent ' . $destination . ' chaque année approximativement ?'],
            ['theme' => 'PERSONNAGES CÉLÈBRES', 'exemple' => 'Quel artiste, scientifique ou personnalité historique est originaire de ' . $destination . ' ?'],
            ['theme' => 'ANECDOTE INSOLITE', 'exemple' => 'Quel fait surprenant, record ou particularité insolite caractérise ' . $destination . ' ?'],
            ['theme' => 'TOURISME PRATIQUE', 'exemple' => 'Quelle activité ou visite est absolument incontournable lors d\'un séjour à ' . $destination . ' ?'],
            ['theme' => 'COMPARAISON', 'exemple' => 'Lequel de ces pays partage une frontière ou une forte similitude culturelle avec ' . $destination . ' ?'],
            ['theme' => 'SPÉCIALITÉ ARTISANALE', 'exemple' => 'Quel artisanat, produit local ou souvenir typique ramène-t-on de ' . $destination . ' ?'],
        ];

        shuffle($allThemes);
        $selected = array_slice($allThemes, 0, self::QUESTIONS_COUNT);

        $themeInstructions = '';
        foreach ($selected as $i => $t) {
            $themeInstructions .= sprintf(
                "  • Question %d → Thème OBLIGATOIRE : %s\n    Exemple de tournure : \"%s\"\n\n",
                $i + 1,
                $t['theme'],
                $t['exemple']
            );
        }

        $prompt = <<<PROMPT
Tu es un créateur de quiz touristiques expert et créatif.
Génère exactement 5 questions de quiz sur la destination : "{$destination}".

THÈMES OBLIGATOIRES — une question différente par thème :
{$themeInstructions}
RÈGLES ABSOLUES :
1. Chaque question DOIT respecter le thème qui lui est assigné ci-dessus
2. STRICTEMENT INTERDIT de commencer une question par :
   - "Quel est le nom de..."
   - "Comment s'appelle..."
   - "Quelle est la capitale de..."
   - "Qui est..."
3. Utilise des tournures variées : "Combien", "Depuis quand", "Lequel parmi", "En quelle année",
   "Quelle particularité", "Pourquoi", "Quel type de", "Dans quelle région", etc.
4. Chaque question doit avoir exactement 4 choix crédibles et plausibles (pas de réponses absurdes)
5. La valeur de "correct" doit être STRICTEMENT IDENTIQUE (même casse, mêmes espaces) à l'un des éléments de "choices"
6. Niveau de difficulté : moyen — intéressant mais accessible

RETOURNE UNIQUEMENT ce tableau JSON brut, SANS texte avant, SANS texte après, SANS balises markdown :
[
  {
    "question": "...",
    "choices": ["...", "...", "...", "..."],
    "correct": "..."
  }
]
PROMPT;

        $response = $this->httpClient->request(
            'POST',
            'https://api.groq.com/openai/v1/chat/completions',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => $this->groqModel,
                    'temperature' => 0.9,
                    'max_tokens'  => 1500,
                    'messages'    => [
                        [
                            'role'    => 'system',
                            'content' => 'Tu es un expert en quiz touristiques créatifs et variés. Chaque question aborde un aspect DIFFÉRENT de la destination. Tu réponds UNIQUEMENT en JSON valide, sans texte ni balise supplémentaire.',
                        ],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ],
            ]
        );

        $statusCode = $response->getStatusCode();
        if ($statusCode !== Response::HTTP_OK) {
            $errorBody = $response->getContent(false);
            $errorData = json_decode($errorBody, true);
            $errorMsg  = $errorData['error']['message'] ?? $errorBody;
            throw new \RuntimeException(sprintf('Erreur API Groq (%d): %s', $statusCode, $errorMsg));
        }

        $body    = $response->toArray();
        $rawText = $body['choices'][0]['message']['content']
            ?? throw new \RuntimeException('Réponse Groq invalide : champ content manquant.');

        $questions = $this->parseJsonQuestions($rawText);
        $this->validateQuestions($questions);

        return $questions;
    }

    private function parseJsonQuestions(string $raw): array
    {
        $cleaned = preg_replace('/```(?:json)?\s*([\s\S]*?)\s*```/i', '$1', $raw);

        if (preg_match('/(\[[\s\S]*\])/u', $cleaned, $matches)) {
            $cleaned = $matches[1];
        }

        $decoded = json_decode(trim($cleaned), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException(
                'JSON invalide reçu de Groq : ' . json_last_error_msg() . '. Réponse brute : ' . substr($raw, 0, 400)
            );
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('La réponse Groq n\'est pas un tableau JSON valide.');
        }

        return $decoded;
    }

    private function validateQuestions(array $questions): void
    {
        $count = count($questions);
        if ($count !== self::QUESTIONS_COUNT) {
            throw new \RuntimeException(
                sprintf('Nombre de questions incorrect. Attendu : %d, Reçu : %d.', self::QUESTIONS_COUNT, $count)
            );
        }

        foreach ($questions as $i => $q) {
            $num = $i + 1;

            if (empty($q['question']) || !is_string($q['question'])) {
                throw new \RuntimeException("Question $num : champ \"question\" manquant ou invalide.");
            }

            if (!isset($q['choices']) || !is_array($q['choices']) || count($q['choices']) !== self::CHOICES_COUNT) {
                throw new \RuntimeException("Question $num : doit avoir exactement " . self::CHOICES_COUNT . " choix.");
            }

            if (empty($q['correct']) || !is_string($q['correct'])) {
                throw new \RuntimeException("Question $num : champ \"correct\" manquant ou invalide.");
            }

            $normalizedCorrect = $this->normalizeAnswer($q['correct']);
            $normalizedChoices = array_map([$this, 'normalizeAnswer'], $q['choices']);

            if (!in_array($normalizedCorrect, $normalizedChoices, true)) {
                throw new \RuntimeException(
                    sprintf('Question %d : la réponse correcte "%s" ne correspond à aucun des choix proposés.', $num, $q['correct'])
                );
            }
        }
    }

    private function normalizeAnswer(string $answer): string
    {
        $answer = mb_strtolower(trim($answer), 'UTF-8');

        if (function_exists('normalizer_normalize')) {
            $answer = \Normalizer::normalize($answer, \Normalizer::FORM_C) ?: $answer;
        }

        return $answer;
    }
}